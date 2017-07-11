<?php
namespace R\Lib\DBAL;

class SQLAnalyzer
{
    public static function analyzeMysqlExplain($ts)
    {
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
