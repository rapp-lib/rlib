<?php
namespace R\Lib\DBAL;

class SQLAnalyzer
{
    public static function analyzeMysqlExplain($explain, $st)
    {
        $analyzed = array();
        foreach ($explain as $t) {
            $t["Extra"] = array_map("trim",explode(';',$t["Extra"]));
            $is_fow_rows = $t["rows"] < 100;
            $is_seq_scan = $t["type"] == "ALL";
            $is_index_seq_scan = $t["type"] == "index";
            $is_no_possible_keys = ! $t["possible_keys"];
            $is_dep_sq = $t["select_type"] == "DEPENDENT SUBQUERY";
            $is_seq_join = in_array("Using join buffer", $t["Extra"]);
            $is_using_where = in_array("Using where", $t["Extra"]);

            $msg = "";
            if ($is_fow_rows) {
                // 行数が少ない場合はINDEXの設定状況のみ確認
                if ($is_seq_scan && $is_using_where && $is_no_possible_keys) $msg = "INDEXが設定されていない";
            } elseif ($is_seq_scan && $is_using_where) {
                if ($is_dep_sq) $msg = "INDEXが適用されない相関サブクエリ";
                elseif ($is_seq_join) $msg = "INDEXが適用されないJOIN";
                else $msg = "INDEXが適用されないWHERE句";
            } elseif ($is_index_seq_scan) $msg = "INDEX(".$t["key"].")の全件走査になるWHERE句";
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
