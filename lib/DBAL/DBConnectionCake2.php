<?php
namespace R\Lib\DBAL;

class DBConnectionCake2 implements DBConnection
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
    public function quoteName($name)
    {
        return "`".addcslashes($name,'`')."`";
    }
    public function quoteValue($value)
    {
        return $this->getDS()->getConnection()->quote($value);
    }

// -- SQL発行

    /**
     * SQLを発行して結果を取得
     */
    public function exec ($st, $params=array())
    {
        $start_ms = microtime(true);
        try {
            // SQL発行
            $result = $this->getDS()->execute($st);
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
        }
        // SQL発行後のレポート
        $params["elapsed"] = round((microtime(true) - $start_ms)*1000,2)."ms";
        if (app()->debug() && ! $error) {
            $analyzed = $this->analyzeSql($st);
            if ($analyzed["msg"]) $params["explain"] = $analyzed["msg"];
        }
        report('SQL Exec : '.$st, $params);
        if ($error) report_error('SQL Error : '.implode(' , ',$error), array_merge($params,array("SQL"=>$st)));
        foreach ((array)$analyzed["warn"] as $msg) {
            report('Bad SQL '.$msg,array(
                "Full Explain" => $analyzed["full"],
            ));
        }
        return $result;
    }

// -- 結果の取得

    /**
     * Select結果の次の1件を取得
     */
    public function fetch ($exec_return)
    {
        $this->getDS()->_result = $exec_return;
        if ( ! $this->getDS()->hasResult()) return false;
        $this->getDS()->resultSet($exec_return);
        $result = $this->getDS()->fetchResult();
        if ( ! $result) return false;
        // PDO::getColumnMetaで階層構造を構成している（ $t["Alias"]["Key"]）
        return $result;
    }
    public function fetchAll($exec_return)
    {
        $results = array();
        while (($result = $exec_return->fetch()) !== false) $results[] = $result;
        return $result;
    }
    public function lastInsertId ($table_name=null, $pkey_name=null)
    {
        return $this->getDS()->lastInsertId($table_name);
    }

// -- トランザクション制御

    public function begin ()
    {
        $this->getDS()->begin();
    }
    public function commit ()
    {
        $this->getDS()->commit();
    }
    public function rollback ()
    {
        $this->getDS()->rollback();
    }

// --

    private $ds;
    private function getDS()
    {
        if ( ! $this->ds) {
            require_once(dirname(__FILE__)."/../../assets/dbi/cake2/rlib_cake2.php");
            require_once(constant("CAKE_DIR").'/Model/ConnectionManager.php');
            if ($this->config["driver"] && ! $this->config["datasource"]) {
                $this->config["datasource"] ='Database/'.str_camelize($this->config["driver"]);
            }
            if (preg_match('!^pdo_(,+)$!',$this->config["driver"],$_)) $this->config["driver"] = $_[1];
            if ($this->config["dbname"]) $this->config["database"] = $db_config["dbname"];
            if ($this->config["user"]) $this->config["login"] = $db_config["user"];
            \ConnectionManager::create($this->ds_name, $this->config);
            $this->ds = \ConnectionManager::getDataSource($this->ds_name);
        }
        return $this->ds;
    }
    private function analyzeSql($st)
    {
        if ($this->config["driver"]==="mysql") {
            if ( ! preg_match('!^SELECT\s!is',$st)) return null;
            $ts = $this->getDS()->getConnection()->query("EXPLAIN ".$st)->fetchAll(\PDO::FETCH_ASSOC);
            return SQLAnalyzer::analyzeMysqlExplain($ts);
        }
    }
}
