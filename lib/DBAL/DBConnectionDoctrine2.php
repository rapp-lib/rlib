<?php
namespace R\Lib\DBAL;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use PDO;
use PDOStatement;

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
    public function quoteName($name)
    {
        return $this->getDS()->quoteIdentifier($name);
    }
    public function quoteValue($value)
    {
        return $this->getDS()->quote($value);
    }
    public function lastInsertId ($table_name=null, $pkey_name=null)
    {
        return $this->getDS()->lastInsertId($table_name);
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
            $stmt = $this->getDS()->query($st);
            $stmt->execute();
        } catch (\Exception $e) {
            $error = $this->getDS()->errorInfo();
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
        return $stmt;
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
    public function fetchAll($stmt)
    {
        $results = array();
        while (($result = $stmt->fetch()) !== false) $results[] = $result;
        return $result;
    }

// --

    private $ds;
    private function getDS()
    {
        if ( ! $this->ds) {
            $options = $this->config["options"] ? new Configuration($this->config["options"]) : null;
            $this->ds = DriverManager::getConnection($this->config, $options);
        }
        return $this->ds;
    }
    private function analyzeSql($st)
    {
        if ($this->config["driver"]==="pdo_mysql") {
            if ( ! preg_match('!^SELECT\s!is',$st)) return null;
            $result = $this->getDS()->query("EXPLAIN ".$st);
            $result->execute();
            $explain = $result->fetchAll(\PDO::FETCH_ASSOC);
            return SQLAnalyzer::analyzeMysqlExplain($explain);
        }
    }
}