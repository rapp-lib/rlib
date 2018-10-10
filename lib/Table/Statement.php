<?php
namespace R\Lib\Table;
use R\Lib\DBAL\SQLStatement;
use R\Lib\DBAL\SQLBuilder;

class Statement extends SQLStatement
{
    protected $table;
    protected $start_ms;
    protected $elapsed_ms;
    public function __construct($table)
    {
        $this->table = $table;
        $this->sql_string = $this->render();
    }
    public function logStart()
    {
        $this->start_ms = microtime(true);
    }
    public function logEnd($error=null)
    {
        $this->elapsed_ms = round((microtime(true) - $this->start_ms)*1000, 2);
        if ( ! $error && app()->debug()) list($warn, $info) = $this->analyze();
        report_info('SQL Exec : '.$this, array("Table"=>$this->table, "Info"=>$info), "SQL");
        if ($warn) report_warning("SQL Warn : ".implode(' , ',$warn), array("SQL"=>"".$this), "SQL");
        if ($error) report_error('SQL Error : '.implode(' , ',$error), array("SQL"=>"".$this));
    }
    /**
     * TableからSQLを組み立てる
     */
    private function render()
    {
        $query = (array)$this->table->getQuery();
        foreach ((array)$query["fields"] as $k => $v) {
            // FieldsのAlias展開
            if ( ! is_numeric($k)) $query["fields"][$k] = array($v,$k);
            // Fieldsのサブクエリ展開
            if (is_object($v) && method_exists($v,"buildQuery")) {
                $query["fields"][$k] = $v = "(".$v->buildQuery("select").")";
            }
        }
        foreach ((array)$query["joins"] as $k => $v) {
            // Joinsのサブクエリ展開
            if (is_object($v[0]) && method_exists($v[0],"buildQuery")) {
                $v[0]->modifyQuery(function($sub_query) use (&$query, $k){
                    $sub_query_statement = $query["joins"][$k][0]->buildQuery("select");
                    if ($sub_query->getGroup()) {
                        //TODO: GroupBy付きのJOINでも異なるDB間でJOINできるようにする
                        $query["joins"][$k][0] = array("(".$sub_query_statement.")", $sub_query->getTableName());
                    } else {
                        $table_name = $sub_query->getTable();
                        // 異なるDB間でのJOIN時にはDBNAME付きのTable名とする
                        if ($query["dbname"]!==$sub_query["dbname"]) {
                            $table_name = $sub_query["dbname"].".".$table_name;
                        }
                        $alias = $sub_query->getAlias();
                        $query["joins"][$k][0] = $alias ? array($table_name, $alias) : $table_name;
                        if ($sub_query["where"]) $query["joins"][$k][1][] = $sub_query["where"];
                    }
                });
            }
        }
        // Updateを物理削除に切り替え
        if ($query["type"]=="update" && $query["delete"]) {
            unset($query["delete"]);
            $query["type"] = "delete";
        }
        // SQLBuilderの作成
        $db = $this->table->getConnection();
        $builder = new SQLBuilder(array(
            "quote_name" => array($db,"quoteName"),
            "quote_value" => array($db,"quoteValue"),
        ));
        return $builder->render($query);
    }
    /**
     * SQLの実行結果の解析
     */
    private function analyze()
    {
        $info = array();
        $warn = array();
        if ($this->elapsed_ms && $this->elapsed_ms>1000) {
            $warn[] = "Slow SQL tooks ".$this->elapsed_ms."ms";
        }
        try {
            $db = $this->table->getConnection();
            if ($this->table->getQuery()->getType()==="select") {
                if ($db->getConfig("driver")==="pdo_mysql") {
                    $explain = $db->fetch($db->exec(new SQLStatement("EXPLAIN ".$this)));
                    $this->analyzeMysqlExplain($explain, $warn, $info);
                }
            }
        } catch (DriverException $e) {
            $warn[] = "EXPLAIN Failed";
        }
        return array($warn, $info);
    }
    /**
     * MySQL Explainの解析
     */
    private function analyzeMysqlExplain($explain, & $warn, & $info)
    {
        $short_explain = array();
        foreach ($explain as $t) {
            // 1行EXPLAINの構築
            $info["short_explain"]["#".$t["id"]] = $t["table"]." ".$t["type"]."/".$t["select_type"]." ".$t["Extra"];
            // テーブル規模の決定
            if ($table_name = app()->table->getAppTableNameByDefTableName($t["table"])) {
                $def = app()->table->getTableDef($table_name);
                $target_scale = $def["target_scale"];
            }
            $scales = array("small"=>100, "midium"=>10000, "large"=>100000);
            if ( ! $target_scale) $target_scale = "midium";
            if (is_numeric($target_scale)) {
                $target_scale = "xlarge";
                foreach ($scales as $k=>$v) if ($target_scale<=$v) $target_scale = $k;
            }
            // EXPLAINからパラメータを抽出する
            $t["Extra"] = array_map("trim",explode(';',$t["Extra"]));
            $is_seq_scan = $t["type"] == "ALL" || $t["type"] == "index";
            $is_no_possible_keys = ! $t["possible_keys"] && ! $t["key"];
            $is_dep_sq = $t["select_type"] == "DEPENDENT SUBQUERY";
            $is_seq_join = in_array("Using join buffer", $t["Extra"]);
            $is_using_where = in_array("Using where", $t["Extra"]);
            // テーブル規模対パラメータから警告を構成する
            $msg = "";
            if ($target_scale=="small") {
            } elseif ($target_scale=="midium") {
                if ($is_using_where && $is_seq_scan) {
                    if ($is_no_possible_keys) {
                        $msg = "INDEXが設定されていないWHERE句";
                    }
                }
            } else {
                if ($is_seq_scan && $is_using_where) {
                    if ($is_dep_sq) $msg = "INDEXが適用されない相関サブクエリ";
                    elseif ($is_seq_join) $msg = "INDEXが適用されないJOIN";
                    else $msg = "全件走査になるWHERE句";
                }
            }
            if ($msg) $warn[] = $msg." on ".$t["table"];
        }
    }
    /**
     * メモリ解放
     */
    public function __release ()
    {
        if ($this->table) {
            unset($this->table);
        }
    }
}
