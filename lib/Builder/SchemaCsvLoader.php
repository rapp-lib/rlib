<?php
namespace R\Lib\Builder;

class SchemaCsvLoader
{
    public function load ($filename)
    {
        $schema = $this->load_schema_csv($filename);
        $schema = $this->complete_schema($schema);
        // report_info("Schema csv loaded.",array("schema" => $schema));
        return $schema;
    }
    private function load_schema_csv ($filename)
    {
        $csv = csv_open($filename, "r", array(
            "ignore_empty_line" => true,
        ));
        // 読み込みモード/切り替え行
        $mode = "";
        $header_line = array();
        // 親の情報にあたる行データ
        $parent_data = array();
        // 組み立て結果となるSchema
        $s = array();
        foreach ($csv->readLines() as $line_num=>$current_line) {
            // コメント行→無視
            if ($current_line[0] == "#") { continue; }
            // コマンド列＝#xxx→読み込みモード切り替え
            if (preg_match('!^#(.+)$!',$current_line[0],$match)) {
                $mode = $current_line[0];
                $header_line = $current_line;
                $parent_data = array();
                continue;
            }
            // モード切替列で意味に関連付け
            $current_data = array();
            foreach ($current_line as $k => $v) {
                // コマンド列→無視
                if ($k == 0) { continue; }
                $current_data[$header_line[$k]] = trim($v);
            }
            // 空行
            if ( ! $current_data) { continue; }
            // #tables:table行
            if ($mode == "#tables" && strlen($current_data["table"])) {
                $parent_data = $current_data;
                $ref = & $s["tables"][$current_data["table"]];
            // #tables:col行
            } elseif ($mode == "#tables" && $parent_data["table"] && strlen($current_data["col"])) {
                $ref = & $s["cols"][$parent_data["table"]][$current_data["col"]];
            // #pages:controller行
            } elseif ($mode == "#pages" && strlen($current_data["controller"])) {
                $parent_data = $current_data;
                $ref = & $s["controller"][$current_data["controller"]];
            // #pages:action行
            } elseif ($mode == "#pages" && $parent_data["controller"] && strlen($current_data["action"])) {
                $ref = & $s["page"][$parent_data["controller"]][$current_data["action"]];
            // 不正な行
            } else {
                report_warning("Irregular schema-record",array(
                    "line_num" => $line_num+1,
                    "current_data" =>$current_data,
                    "parent_data" =>$parent_data,
                    "header_line" =>$header_line,
                ));
                continue;
            }
            // 参照へのデータ登録
            foreach ($current_data as $k => $v) {
                if (strlen($v)
                        && ! ($mode == "#tables" && in_array($k,array("other","table","col")))
                        && ! ($mode == "#pages" && in_array($k,array("other","controller","action")))) {
                    $this->parse_other($ref[$k], $v);
                }
            }
            $this->parse_other($ref, $current_data["other"]);
        }
        return $s;
    }
    /**
     * other属性のパース（改行=区切り）
     */
    private function parse_other ( & $ref, $str)
    {
        foreach (preg_split("!(\r?\n)|\|!",$str) as $sets) {
            if (preg_match('!^(.+?)=(.+)$!',$sets,$match))  {
                $ref[trim($match[1])] = $this->trim_value($match[2]);
            } elseif (strlen(trim($sets))) {
                $ref = $this->trim_value($sets);
            }
        }
    }
    /**
     * 値の加工
     */
    private function trim_value ($value)
    {
        $value = trim($value);
        if (preg_match('!^"(.*?)"$!',$value,$match)) {
            $value = (string)$match[1];
        } elseif (is_numeric($value)) {
            $value = (int)$value;
        } elseif (preg_match('!^(\{.*\}|\[.*\])$!i', $value)) {
            $value = json_decode($value, true);
        } elseif (preg_match('!^true$!i', $value)) {
            $value = true;
        } elseif (preg_match('!^false$!i', $value)) {
            $value = false;
        } elseif (preg_match('!^null$!i', $value)) {
            $value = null;
        }
        return $value;
    }
    /**
     * スキーマの補完
     */
    private function complete_schema ($schema)
    {
        // Controllerの補完
        foreach ($schema["controller"] as $name => & $c) {
            $c["name"] = $name;
            $c["access_as"] = $c["access_as"] ?: "guest";
            $c["priv_required"] = $c["priv_required"] ?: false;
        }
        // テーブルごとに処理
        foreach ($schema["tables"] as $t_name => & $t) {
            $cols = (array)$schema["cols"][$t_name];
            $t["name"] = $t_name;
            // カラムごとに処理
            foreach ($cols as $tc_name => $tc) {
                $tc["name"] = $tc_name;
                $t["cols"][$tc["name"]] = $tc;
            }
            $t["cols"] = (array)$t["cols"];
        }
        $tables_def = array();
        // DB初期化SQL構築
        foreach ($schema["tables"] as $t_name => & $t) {
            $t_def = & $tables_def[$t_name];
            $t_def = (array)$t["def"];
            $t_def["table"] = $t_name;
            $t_def["pkey"] = preg_replace('!^'.preg_quote($t_name).'\.!', '', $t["pkey"]);
            foreach ((array)$t["cols"] as $tc_name => $tc) {
                $tc_name = preg_replace('!^'.preg_quote($t_name).'\.!', '', $tc_name);
                $tc_def = & $tables_def[$t_name]["cols"][$tc_name];
                $tc_def = (array)$tc["def"];
                $tc_def["name"] = $tc_def["name"] ?: $tc_name;
                $tc_def["comment"] = $tc_def["comment"] ?: $tc["label"];
                // INDEXの登録
                if ($tc_def["index"]) {
                    $index_name = $t_def["table"]."_idx_".$tc_def["index"];
                    $t_def["indexes"][$index_name]["column"][] = $tc_def["name"];
                }
            }
        }
        return $schema;
    }
}
