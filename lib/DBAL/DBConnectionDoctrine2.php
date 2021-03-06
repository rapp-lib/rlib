<?php
namespace R\Lib\DBAL;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Exception\DriverException;
use PDO;
use PDOStatement;
use R\Lib\Util\Cli;

class DBConnectionDoctrine2 implements DBConnection
{
    private $ds_name;
    private $config;
    public function __construct($ds_name, $config)
    {
        $this->ds_name = $ds_name;
        $this->config = $config;
    }
    public function getDbName()
    {
        return $this->config["dbname"];
    }
    public function getConfig($key)
    {
        return \R\Lib\Util\Arr::array_get($this->config, $key);
    }
    public function lastInsertId ($table_name=null, $pkey_name=null)
    {
        return $this->getDS()->lastInsertId($table_name);
    }
    public function quoteName($name)
    {
        return $this->getDS()->quoteIdentifier($name);
    }
    public function quoteValue($value)
    {
        return $this->getDS()->quote($value);
    }

    private $builder = null;
    public function getRenderer()
    {
        if ( ! $this->builder) {
            $this->builder = new SQLBuilder(array(
                "quote_name"=>array($this, "quoteName"),
                "quote_value"=>array($this, "quoteValue"),
            ));
        }
        return $this->builder;
    }

// -- トランザクション操作

    private $transaction_nest = 0;
    public function transaction ($callback, $args=array(), $retry=0)
    {
        try {
            if ($this->transaction_nest === 0) {
                $this->getDS()->beginTransaction();
            }
            $current_transaction_nest = $this->transaction_nest++;
            $result = call_user_func_array($callback, $args);
            $this->transaction_nest--;
            if ($this->transaction_nest === 0) {
                $this->getDS()->commit();
            }
            return $result;
        } catch (\Exception $e) {
            // DBRollbackException以外の発行で抜けている場合は、Rollbackする
            if ($this->transaction_nest > 0) {
                $this->getDS()->rollback();
                $this->transaction_nest = 0;
            }
            // 最外殻のDBRollbackException以外であればExceptionを再発行
            if ( ! $e instanceof \R\Lib\DBAL\DBRollbackException) {
                throw $e;
            } elseif ($current_transaction_nest > 0) {
                throw $e;
            }
            return false;
        }
    }
    public function begin ()
    {
        $this->getDS()->beginTransaction();
    }
    public function commit ()
    {
        $this->getDS()->commit();
    }
    public function rollback ()
    {
        $this->getDS()->rollback();
        // Transactionネスト内であればException発行で抜ける
        if ($this->transaction_nest > 0) {
            $this->transaction_nest = 0;
            throw new DBRollbackException("Rollback transaction");
        }
    }

// -- SQL発行

    /**
     * SQLを発行して結果を取得
     */
    public function exec ($st, $params=array())
    {
        $start_ms = microtime(true);
        if ($st instanceof SQLStatement) $st->logStart();
        try {
            $stmt = $this->getDS()->query("".$st);
        } catch (\Exception $e) {
            $error = $this->getErrorInfo();
        }
        if ( ! $stmt) $error[] = "Query failed";
        if ($st instanceof SQLStatement) $st->logEnd($error);
        return $stmt;
    }
    /**
     * 最後に発生したエラーを取得
     */
    public function getErrorInfo ()
    {
        $error = $this->getDS()->errorInfo();
        if ($error && $error[0]==="00000") unset($error);
        return $error;
    }
    /**
     * Select結果の次の1件を取得
     */
    public function fetch ($stmt)
    {
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if ( ! $result) return false;
        if ( ! $stmt instanceof PDOStatement) return false;
        if ( ! $stmt->map) for ($i=0, $num=$stmt->columnCount(); $i<$num; $i++) {
            $col = $stmt->getColumnMeta($i);
            $stmt->map[$i] = array($col['table'] ?: 0, $col['name'], $col['native_type'] ?: "string");
        }
        $result_copy = array();
        foreach ($stmt->map as $i=>$c) $result_copy[$c[0]][$c[1]] = $result[$i];
        return $result_copy;
    }

// --

    /**
     * Doctrine接続の取得
     * SchemaDiffなどに使用する
     */
    public function getDoctrineConnection()
    {
        return $this->getDS();
    }
    /**
     * ダンプデータの出力
     * 接続設定以外参照していないので外部化可能
     */
    public function dumpData($filename)
    {
        if ($this->config["driver"]==="pdo_mysql") {
            $args = array();
            $args[] = array("-B", $this->config["dbname"]);
            if ($this->config["host"]) $args[] = array("-h", $this->config["host"]);
            if ($this->config["port"]) $args[] = array("-P", $this->config["port"]);
            if ($this->config["user"]) $args[] = array("-u", $this->config["user"]);
            if ($this->config["password"]) $args[] = array("--password=".$this->config["password"]);
            list($ret, $out, $err, $cmd) = Cli::exec(array("mysqldump", $args, ">"=>array($filename)));
            if ( ! $ret && preg_match('!\.gz$!', $filename)) {
                list($ret, $out, $err, $cmd) = Cli::exec($cmd, array("gzip", $filename));
            }
            if ($ret) {
                report_warning("mysqldumpが正常に実行できませんでした",array(
                    "cmd" => $cmd,
                    "err" => $err,
                ));
                return false;
            }
            return true;
        } else {
            report_warning("mysqldumpが実行できません",array(
                "driver" => $this->config["driver"],
            ));
            return false;
        }
    }
    /**
     * RDBMS型に対応するClassの登録
     */
    public function setTypes($types)
    {
        foreach ((array)$types as $type_name => $type_class) {
            if (Type::hasType($type_name)) {
                Type::overrideType($type_name, $type_class);
            } else {
                Type::addType($type_name, $type_class);
            }
        }
    }

// --

    private $ds;
    private function getDS()
    {
        if ( ! $this->ds) {
            // データ型の登録
            $this->setTypes($this->config["types"]);
            unset($this->config["types"]);
            // configの構築
            $config = $this->config["config"] ? new Configuration($this->config["config"]) : null;
            unset($this->config["config"]);
            // 接続
            $this->ds = DriverManager::getConnection($this->config, $config);
        }
        return $this->ds;
    }
}
