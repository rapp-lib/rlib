<?php

    //-------------------------------------
    // schema.config.csv→config生成
    function rdoc_entry_schema_csv_to_registry ($options=array()) {

        $obj =obj("Rdoc_Schema_WebappBuilderCreateSchema");
        $obj->init(array(
            "schema"=>1,
            "src"=>"csv",
            "force"=>1,
        ));
        $obj->create_schema();
    }

//-------------------------------------
//
class Rdoc_Schema_WebappBuilderCreateSchema extends WebappBuilder {

    //-------------------------------------
    // csvからschema.config.phpを生成する
    public function create_schema () {

        report("HistoryKey: ".$this->history);
        $this->append_history("memo", date("Y/m/d H:i"), "create_schema_registry_from_csv");

        $src_file =registry("Path.webapp_dir")."/config/schema.config.csv";

        if ( ! file_exists($src_file)) {

            report_error("src_file is-not exists",array(
                "src_file" =>$src_file,
            ));
        }

        $data =$this->load_schema_csv($src_file);

        $this->deploy_src(registry("Path.webapp_dir")."/config/_schema.config.php", $data);
    }

    //-------------------------------------
    // SchemaConfigPHP：SchemaCSVファイルを読み込んで、SchemaConfigのPHPを生成する
    protected function load_schema_csv ($filename) {

        $csv =new CSVHandler($filename,"r",array(
            "file_charset" =>"SJIS-WIN",
        ));

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
                $ref =& $s["Schema.tables.".$current_data["table"]];

            // #tables:col行
            } elseif ($mode == "#tables" && $parent_data["table"] && strlen($current_data["col"])) {

                $ref =& $s["Schema.cols.".$parent_data["table"]][$current_data["col"]];

            // #pages:controller行
            } elseif ($mode == "#pages" && strlen($current_data["controller"])) {

                $parent_data =$current_data;
                $ref =& $s["Schema.controller"][$current_data["controller"]];

            // #pages:action行
            } elseif ($mode == "#pages" && $parent_data["controller"] && strlen($current_data["action"])) {

                $ref =& $s["Schema.page"][$parent_data["controller"]][$current_data["action"]];

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

        report("Schema csv loaded.",array("schema" =>$s));

        // スクリプト生成
        $g =new ScriptGenerator;
        $g->node("root",array("p",array(
            array("c","Schama created from csv-file."),
            array("v",array("c","registry",array(
                array("a",$g->make_array_node($s)),
            )))
        )));

        return $g->get_script();
    }

    //-------------------------------------
    // other属性のパース（改行=区切り）
    protected function parse_other ( & $ref, $str) {

        foreach (preg_split("!(\r?\n)|\|!",$str) as $sets) {

            if (preg_match('!^(.+?)=(.+)$!',$sets,$match))  {

                $ref[trim($match[1])] =$this->trim_value($match[2]);

            } elseif (strlen(trim($sets))) {

                $ref =$this->trim_value($sets);
            }
        }
    }

    //-------------------------------------
    // 値の加工
    protected function trim_value ($value) {

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
}