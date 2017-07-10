<?php
namespace R\Lib\DBAL;
class Connection_Cake2
{
    private $ds_name;
    private $info;
    private $last_error = null;
    public function __construct($ds_name, $info)
    {
        $this->ds_name = $ds_name;
        $this->info = $info;
        require_once(dirname(__FILE__)."/../cake2/rlib_cake2.php");
        require_once(constant("CAKE_DIR").'/Model/ConnectionManager.php');
        // 旧cakeとの互換処理
        if ($info["driver"] && ! $info["datasource"]) {
            $info["datasource"] = 'Database/'.str_camelize($info["driver"]);
        }
        ConnectionManager::create($ds_name, $info);
    }
    public function getDs()
    {
        return ConnectionManager::getDataSource($this->ds_name);
    }

// SQL発行

    /**
     * SQLを発行して結果を取得
     */
    public function execute ($st, $params=null)
    {
        $this->last_error = null;
        try {
            return $this->ds->execute($st);
        } catch (\PDOException $e) {
            $this->last_error = $e->errorInfo;
           //$this->ds->lastError();
        }
        return null;
    }
    public function getLastError ()
    {
        return $this->last_error;
    }
    public function analyzeSql ($st)
    {
    }

// -- 結果の取得

    /**
     * Select結果の次の1件を取得
     */
    public function fetch ($result_source)
    {
        $this->ds->_result = $result_source;
        // 結果セットが無効であればnullを返す
        if ( ! $this->ds->hasResult()) {
            return null;
        }
        $this->ds->resultSet($result_source);
        $result = $this->ds->fetchResult();
        // データがなければnullを返す
        if ( ! $result) {
            return null;
        }
        // getColumnMetaで階層構造を構成している（ $t["Alias"]["Key"]）
        if ( ! $hydrateFlatten) {
            return $result;
        }
        $result_copy =array();
        foreach ((array)$result as $k1 => $v1) {
            foreach ((array)$v1 as $k2 => $v2) {
                $key = $k2;
                // if ( ! is_numeric($k1)) {
                //     $key =  $k1.".".$k2;
                // }
                $result_copy[$key] = & $result[$k1][$k2];
            }
        }
        return $result_copy;
    }
    public function lastInsertId ($table_name=null, $pkey_name=null)
    {
        return $this->ds->lastInsertId($table_name, $pkey_name);
    }
    public function lastNumRows ()
    {
        return $this->ds->lastNumRows();
    }
    public function lastAffected ()
    {
        return $this->ds->lastAffected();
    }

// -- トランザクション制御

    public function begin ()
    {
        $this->ds->begin();
    }
    public function commit ()
    {
        $this->ds->commit();
    }
    public function rollback ()
    {
        $this->ds->rollback();
    }

// -- 旧実装

    /**
     * テーブル一覧を取得
     * @deprecated
     */
    public function descTables ()
    {
        return $this->ds->listSources();
    }
    /**
     * テーブル構造解析
     * @deprecated
     */
    public function desc ($table_name)
    {
        return $this->ds->describe($table_name);
    }
    public function __execute ($st, $params=null)
    {
        $start_time =microtime(true);
        try {
            $result =$this->ds->execute($st);
        } catch (\PDOException $e) {
            $this->ds->error = implode(' ',$e->errorInfo);
        }
        $elapsed =round((microtime(true) - $start_time)*1000,2)."ms";
        // SQL文の調査
        if ($this->check_driver("is_support_analyze_sql")
                && app()->debug() && ! $this->ds->error) {
            $explain =$this->analyze_sql($st,$elapsed);
            if ($explain["msg"]) {
                $report_context["Explain"] =$explain["msg"];
            }
        }
        report('SQL Exec',array_merge($report_context,array(
            "Statement" =>$st,
            "Elapsed" =>$elapsed,
        )));
        if ($explain["warn"]) {
            foreach ($explain["warn"] as $warn) {
                report('Bad SQL '.$warn,array(
                    "Statement" =>$st,
                    "Full Explain" =>$explain["full"],
                ));
            }
        }
        if ($this->ds->error) {
            // トランザクション起動中であればRollbackする
            if ($this->transaction_stack) {
                $this->rollback();
            }
            report_error('SQL Error',array(
                "Statement" =>$st,
                "Error" =>$this->ds->error,
            ));
        }
        return $result;
    }
    /**
     * Select結果を全件ロード
     * @deprecated
     */
    public function __fetch_all ($result_source) {
        $result =array();
        while ($row =$this->fetch($result_source)) {
            $result[] =$row;
        }
        return $result;
    }
    public function __analyzeSql ($st)
    {
        if ( ! preg_match('!^SELECT\s!is',$st)) {
            return null;
        }
        $result = $this->ds->execute("EXPLAIN ".$st);
        $ts = $this->fetch_all($result);
        while ($row =$this->fetch($result_source)) {
        }
        $explain["full"] =array();
        $explain["msg"] =array();
        $explain["warn"] =array();
        foreach ($ts as $i =>$t) {
            $t["Extra"] =array_map("trim",explode(';',$t["Extra"]));
            $msg =$t["select_type"];
            if ($t["type"]) {
                $msg .=".".$t["type"];
            }
            if ($t["table"]) {
                $msg .=" , Table=".$t["table"];
            }
            if ($t["rows"]) {
                $msg .="(".$t["rows"].")";
            }
            if ($t["key"]) {
                $msg .=" , Index=".$t["key"];
            }
            if ($t["Extra"]) {
                $msg .=" , ".implode(" , ",$t["Extra"])."";
            }
            $explain["msg"][] =$msg;
            $full =$t;
            $full["Extra"] =implode(",",$t["Extra"]);
            $explain["full"][] =$full;
            if ($t["type"] == "index") {
                if ($t["select_type"] != "PRIMARY") {
                    $explain["warn"][] ="[INDEX全件スキャン] ".$msg;
                }
            }
            if ($t["type"] == "ALL") {
                $explain["warn"][] ="[★全件スキャン] ".$msg;
            }
            if ($t["select_type"] == "DEPENDENT SUBQUERY") {
                if ($t["type"] == "ref" || $t["type"] == "eq_ref") {
                    $explain["warn"][] ="[参照相関SQ] ".$msg;
                } elseif ($t["type"] == "unique_subquery") {
                    $explain["warn"][] ="[U-INDEX相関SQ] ".$msg;
                } elseif ($t["type"] == "index_subquery") {
                    $explain["warn"][] ="[INDEX相関SQ] ".$msg;
                } else {
                    $explain["warn"][] ="[★★全件スキャン相関SQ] ".$msg;
                }
            }
            foreach ($t["Extra"] as $extra_msg) {
                // if ($extra_msg == "Using filesort") {
                //     $explain["warn"][] ="[INDEXのないソート] ".$msg;
                // }
                // if ($extra_msg == "Using temporary") {
                //     $explain["warn"][] ="[一時テーブルの生成] ".$msg;
                // }
                if ($extra_msg == "Using join buffer") {
                    $explain["warn"][] ="[★★全件スキャンJOIN] ".$msg;
                }
            }
        }
        return $explain;
    }
}
