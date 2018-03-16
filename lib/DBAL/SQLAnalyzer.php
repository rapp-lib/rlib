<?php
namespace R\Lib\DBAL;

class SQLAnalyzer
{
    public static function analyzeMysqlExplain($explain, $st, $params)
    {
        $analyzed = array();
        foreach ($explain as $t) {
            $t["Extra"] = array_map("trim",explode(';',$t["Extra"]));
            $is_few_rows = $t["rows"] < 1000;
            $is_seq_scan = $t["type"] == "ALL";
            $is_index_seq_scan = $t["type"] == "index";
            $is_no_possible_keys = ! $t["possible_keys"] && ! $t["key"];
            $is_dep_sq = $t["select_type"] == "DEPENDENT SUBQUERY";
            $is_seq_join = in_array("Using join buffer", $t["Extra"]);
            $is_using_where = in_array("Using where", $t["Extra"]);

            $msg = "";
            // 一定時間未満で完了するSQLの場合はINDEXの設定状況のみ確認
            if ($params["elapsed_ms"] < 1000) {
                if ($is_using_where && $is_no_possible_keys && ($is_seq_scan || $is_index_seq_scan)) {
                    $msg = "INDEXが適用されないWHERE句";
                }
            } else {
                if ($is_seq_scan && $is_using_where) {
                    if ($is_dep_sq) $msg = "INDEXが適用されない相関サブクエリ";
                    elseif ($is_seq_join) $msg = "INDEXが適用されないJOIN";
                    else $msg = "INDEXが適用されないWHERE句";
                } elseif ($is_index_seq_scan) $msg = "INDEX(".$t["key"].")の全件走査になるWHERE句";
            }
            if ($msg) {
                $msg .= " on ".$t["table"];
                $analyzed["hint"][] = array($msg, array(
                    "statement"=>$st,
                    "explain"=>$t,
                    "full_explain"=>$explain,
                ));
            }
        }
        return $analyzed;
    }
}
