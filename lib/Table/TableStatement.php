<?php
namespace R\Lib\Table;
use R\Lib\DBAL\SQLStatement;
use R\Lib\DBAL\SQLBuilder;

class TableStatement extends SQLStatement
{
    protected $table;
    public function __construct($table)
    {
        $this->table = $table;
        $this->sql_string = $this->render();
    }
    protected function render()
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
                        $query["joins"][$k][0] = $table_name;
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
        $this->builder = new SQLBuilder(array(
            "quote_name" => array($db,"quoteName"),
            "quote_value" => array($db,"quoteValue"),
        ));
        return $this->builder->render($query);
    }
    public function logStart()
    {
        $this->start_ms = microtime(true);
    }
    public function logEnd($error=null)
    {
        $params["elapsed_ms"] = round((microtime(true) - $start_ms)*1000,2);
        if (app()->debug()) report_info('SQL Exec : '.$st, array("SQL"=>$this));
        if ($error) report_error('SQL Error : '.implode(' , ',$error), array("SQL"=>$this));
        //if (app()->debug()) $params = $this->analyzeSql($st, $params);
    }
    private function analyzeSql($st, $params)
    {
        try {
            if ($params["elapsed_ms"] && $params["elapsed_ms"]>5000) {
                report_warning("SQL Warning : Slow SQL tooks ".(round($params["elapsed_ms"]/1000,1)." sec").".", array("statement"=>$st));
            }
            if ($this->config["driver"]==="pdo_mysql") {
                if ( ! preg_match('!^SELECT\s!is',$st)) return;
                $result = $this->getDS()->query("EXPLAIN ".$st);
                $explain = $result->fetchAll(\PDO::FETCH_ASSOC);
                $analyzed = SQLAnalyzer::analyzeMysqlExplain($explain, $st, $params);
                if ($analyzed) foreach ($analyzed["hint"] as $hint) {
                    report_warning("SQL Warning : ".$hint[0], $hint[1]);
                }
            }
        } catch (DriverException $e) {
            report_warning("SQL Warning : EXPLAIN Failed.", array("statement"=>$st));
        }
        return $params;
    }
}
