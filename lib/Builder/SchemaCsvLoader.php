<?php
namespace R\Lib\Builder;

class SchemaCsvLoader
{
    public function load ($filename)
    {
        $schema = $this->load_schema_csv($filename);
        $schema = $this->complete_schema($schema);
        report("Schema csv loaded.",array("schema" => $schema));
        return $schema;
    }
    private function load_schema_csv ($filename)
    {
        $csv =util("CSVHandler",array($filename,"r",array(
            "file_charset" =>"SJIS-WIN",
        )));
        // 読み込みモード/切り替え行
        $mode ="";
        $header_line =array();
        // 親の情報にあたる行データ
        $parent_data =array();
        // 組み立て結果となるSchema
        $s =array();
        foreach ($csv->read_all() as $current_line) {
            // コメント行→無視
            if ($current_line[0] == "#") { continue; }
            // コマンド列＝#xxx→読み込みモード切り替え
            if (preg_match('!^#(.+)$!',$current_line[0],$match)) {
                $mode =$current_line[0];
                $header_line =$current_line;
                $parent_data =array();
                continue;
            }
            // モード切替列で意味に関連付け
            $current_data =array();
            foreach ($current_line as $k => $v) {
                // コマンド列→無視
                if ($k == 0) { continue; }
                $current_data[$header_line[$k]] =trim($v);
            }
            // 空行
            if ( ! $current_data) { continue; }
            // #tables:table行
            if ($mode == "#tables" && strlen($current_data["table"])) {
                $parent_data =$current_data;
                $ref =& $s["tables"][$current_data["table"]];
            // #tables:col行
            } elseif ($mode == "#tables" && $parent_data["table"] && strlen($current_data["col"])) {
                $ref =& $s["cols"][$parent_data["table"]][$current_data["col"]];
            // #pages:controller行
            } elseif ($mode == "#pages" && strlen($current_data["controller"])) {
                $parent_data =$current_data;
                $ref =& $s["controller"][$current_data["controller"]];
            // #pages:action行
            } elseif ($mode == "#pages" && $parent_data["controller"] && strlen($current_data["action"])) {
                $ref =& $s["page"][$parent_data["controller"]][$current_data["action"]];
            // 不正な行
            } else {
                report_warning("Irregular schema-record",array(
                    "header_line" =>$header_line,
                    "parent_data" =>$parent_data,
                    "current_data" =>$current_data,
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
                $ref[trim($match[1])] =$this->trim_value($match[2]);
            } elseif (strlen(trim($sets))) {
                $ref =$this->trim_value($sets);
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
        } elseif ($value=="true") {
            $value = true;
        } elseif ($value=="false") {
            $value = false;
        } elseif ($value=="null") {
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
            $c["name"] =$name;
            $c["access_as"] = $c["access_as"] ? $c["access_as"] : $c["accessor"];
            // $c["priv_required"] = $c["priv_required"]
            //     ? $c["priv_required"]
            //     : ($c["auth"] ? "true" : "false");
            // $c["header"] ='{{inc route="/include/'.$c["access_as"].'_header.html"}}';
            // $c["footer"] ='{{inc route="/include/'.$c["access_as"].'_footer.html"}}';
        }
        // テーブルごとに処理
        foreach ($schema["tables"] as $t_name => & $t) {
            $cols = (array)registry("Schema.cols.".$t_name);
            $t["name"] =$t_name;
            // pkeyをdef.idから補完
            if ( ! $t["pkey"]) {
                foreach ($cols as $tc_name => $tc) {
                    if ($tc["def"]["id"]) {
                        $t["pkey"] = $tc_name;
                    }
                }
            }
            $syskeys =array("pkey","reg_date","del_flg","update_date");
            foreach ($syskeys as $key) {
                if ($t[$key]) {
                    //$t[$key] =$t_name.".".$t[$key];
                    $syskeys[$key] =$t[$key];
                }
            }
            // カラムごとに処理
            foreach ($cols as $tc_name => $tc) {
                //$tc["name"] =$t_name.".".$tc_name;
                $tc["name"] =$tc_name;
                $tc["short_name"] =$tc_name;
                // データ表現別のオプション付加
                if ($tc['type'] == "date") {
                    //$tc['modifier'] ='|date:"Y/m/d"';
                    //$tc['input_option'] =' range="2010~+5" format="{%l}{%yp}{%mp}{%dp}{%datefix}"';
                }
                if ($tc['type'] == "textarea") {
                    $tc['modifier'] ='|nl2br';
                    //$tc['input_option'] =' cols="40" rows="5"';
                }
                if ($tc['type'] == "text") {
                    //$tc['input_option'] =' size="40"';
                }
                if ($tc['type'] == "password") {
                    $tc['modifier'] ='|hidetext';
                    //$tc['input_option'] =' size="40"';
                }
                if ($tc['type'] == "file") {
                    //$group =$tc['group'] ? $tc['group'] : "public";
                    //$tc['modifier'] ='|userfile:"'.$group.'"';
                    //$tc['input_option'] =' group="'.$group.'"';
                    // FileStorage対応
                    $storage = $tc['storage'] ? $tc['storage'] : "tmp";
                    $tc['field_def'] =' => array("input_convert"=>"file_upload", "storage"=>"'.$storage.'")';
                }
                // Enum対応
                if ($tc['type'] == "select" || $tc['type'] == "radioselect" || $tc['type'] == "checklist") {
                    $tc['enum'] = $tc['enum'] ? $tc['enum'] : $t_name.".".$tc_name;
                }
                if ($tc['type'] == "select" || $tc['type'] == "radioselect") {
                    $tc['modifier'] ='|enum:"'.$tc['enum'].'"';
                    $tc['input_option'] =' enum="'.$tc['enum'].'"';
                }
                if ($tc['type'] == "checklist") {
                    $tc['modifier'] ='|enumeach:"'.$tc['enum'].'"|tostring:" "';
                    $tc['input_option'] =' enum="'.$tc['enum'].'"';
                }
                //$tc['input_option'] .=' class="input-'.$tc['type'].'"';
                // DB上のカラムに対応するcolsに登録
                if ($tc['def']['type'] != "" && $tc['def']['type'] != "virtual") {
                    $t["cols"][$tc["name"]] =$tc;
                }
                // 入力用のfieldsに登録
                if ( ! in_array($tc_name,$syskeys)
                        && $tc['type'] != "key"
                        && $tc['type'] != "virtual"
                        && $tc['type'] != "") {
                    $t["fields"][$tc["name"]] =$tc;
                }
                $t["cols_all"][$tc["name"]] =$tc;
            }
            $t["fields"] =(array)$t["fields"];
            $t["cols"] =(array)$t["cols"];
            $t["cols_all"] =(array)$t["cols_all"];
        }
        $tables_def = array();
        // DB初期化SQL構築
        foreach ($schema["tables"] as $t_name => & $t) {
            $t_def =& $tables_def[$t_name];
            $t_def =(array)$t["def"];
            $t_def["table"] =$t_name;
            $t_def["pkey"] =preg_replace(
                    '!^'.preg_quote($t_name).'\.!',
                    '', $t["pkey"]);
            foreach ((array)$t["cols"] as $tc_name => $tc) {
                $tc_name =preg_replace('!^'.preg_quote($t_name).'\.!', '', $tc_name);
                $tc_def =& $tables_def[$t_name]["cols"][$tc_name];
                $tc_def =(array)$tc["def"];
                $tc_def["name"] =$tc_def["name"]
                        ? $tc_def["name"]
                        : $tc_name;
                $tc_def["comment"] =$tc_def["comment"]
                        ? $tc_def["comment"]
                        : $tc["label"];
                // INDEXの登録
                if ($tc_def["index"]) {
                    $index_name =$t_def["table"]."_idx_".$tc_def["index"];
                    $t_def["indexes"][$index_name]["column"][] =$tc_def["name"];
                }
            }
        }
        return $schema;
    }
}
