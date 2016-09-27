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
    // エントリポイント
    public function create_schema () {

        if ($this->src == "a5er") {

            $this->create_schema_csv_from_a5er();

        } else {

            $this->create_schema_registry_from_csv();
        }
    }

    //-------------------------------------
    // csvからschema.config.phpを生成する
    protected function create_schema_registry_from_csv () {

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
    // a5erからschema.config.csvを生成する
    protected function create_schema_csv_from_a5er () {

        report("HistoryKey: ".$this->history);
        $this->append_history("memo", date("Y/m/d H:i"), "create_schema_csv_from_a5er");

        $src_file =registry("Path.webapp_dir")."/config/schema.config.a5er";

        if ( ! file_exists($src_file)) {

            report_error("src_file is-not exists",array(
                "src_file" =>$src_file,
            ));
        }

        $data =$this->load_schema_a5er($src_file);
        $this->deploy_src(registry("Path.webapp_dir")."/config/schema.config.csv", $data);
    }

    //-------------------------------------
    // A5ER：A5ERファイルを読み込んで、SchemaCSVを生成する
    protected function load_schema_a5er ($filename) {

        $st_cat ='';
        $st_table ="";
        $s =array();

        foreach (file($filename) as $line) {

            $line =trim($line);

            // 空行
            if ( ! $line) {

                continue;

            // カテゴリ表示行（[Entity]等）
            } elseif (preg_match('!^\[([^\]]+)\]$!',$line,$match)) {

                $st_cat =$match[1];
                continue;
            }

            list($line_name, $line_value) =explode('=',$line,2);
            $line_values =$this->split_csv_line($line_value);

            // [Entity]
            if ($st_cat == "Entity") {

                // テーブル物理名
                if ($line_name == "PName") {

                    $t =$line_value;
                    $s["Schema.tables"][$t] =array();

                // テーブル論理名
                } elseif ($line_name == "LName") {

                    $s["Schema.tables"][$t]["label"] =$line_value;

                // フィールド
                } elseif ($line_name == "Field") {

                    list(
                        $lname,
                        $pname,
                        $sql_type,
                        $extra,
                        $keytype,
                        $_,
                        $comment
                    ) =$line_values;

                    $s["Schema.cols"][$t][$pname]["label"] =$lname
                        ? $lname
                        : preg_replace('!\(.+$!','',$comment);

                    list(
                        $s["Schema.cols"][$t][$pname]["type"],
                        $s["Schema.cols"][$t][$pname]["def.type"]
                    ) =$this->convert_sql_type($sql_type);

                    if (strlen($keytype) && $keytype=="0") {

                        $s["Schema.tables"][$t]["pkey"] =$pname;
                    }

                // インデックス
                } elseif ($line_name == "Index") {

                    $s["Schema.tables.".$t]["def.indexes"]
                            =preg_replace('!^=(0,)?!','',$line_value);
                }
            }
        }

        report("Schema A5ER loaded.",array("schema" =>$s));

        // CSV生成
        $csv =new CSVHandler($tempnam=tempnam("/tmp","php_tmpfile-"),"w");

        // ラベル行
        $csv->write_line(array(
            '#tables','table','col','label','def','type','other',
        ));

        foreach ($s["Schema.tables"] as $table_name => $table) {

            // テーブル開始行
            $csv->write_line(array(
                '',
                $table_name,
                '',
                $table["label"],
                '',
                '',
                ($table["pkey"] ? 'pkey='.$table["pkey"] : ''),
            ));

            foreach ($s["Schema.cols"][$table_name] as $col_name => $col) {

                // カラム行
                $csv->write_line(array(
                    '',
                    '',
                    $col_name,
                    $col["label"],
                    $col["def.type"],
                    $col["type"],
                    '',
                ));
            }

            $csv->write_line(array());
        }

        return file_get_contents($tempnam);
    }

    //-------------------------------------
    // A5ER：SQL型名の置換
    protected function convert_sql_type ($sql_type) {

        $type ="text";
        $def_type ="text";

        if (preg_match('!^INT!',$sql_type)) {

            $def_type ="integer";

        } elseif (preg_match('!^VARCHAR!',$sql_type)) {

            $def_type ="string";

        } elseif (preg_match('!^DATE|DATETIME|TIMESTAMP!',$sql_type)) {

            $type ="dateselect";
            $def_type ="datetime";
        }

        return array($type, $def_type);
    }

    //-------------------------------------
    // A5ER：各行の「=」以降のCSV形式部分の分解
    protected function split_csv_line ($line, $e='"', $d=',') {

        $csv_pattern ='/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'
                .$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
        preg_match_all($csv_pattern, trim($line), $matches);
        $csv_data =(array)$matches[1];

        foreach ($csv_data as $k => $v) {

            $csv_data[$k] =preg_replace('!^'.$e.'(.*?)'.$e.'$!','$1',$v);
        }

        return $csv_data;
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
                array("a",$this->get_array_script_node($s)),
            )))
        )));

        return $g->get_script();
    }

    //-------------------------------------
    // 配列構造のScriptNodeを取得
    protected function get_array_script_node ($arr) {

        $n =array();

        foreach ($arr as $k => $v) {

            if (is_array($v)) {

                $n[$k] =array("a",$this->get_array_script_node($v));

            } elseif (is_numeric($v)) {

                $n[$k] =array("d",(int)$v);

            } else {

                $n[$k] =array("s",(string)$v);
            }
        }

        return $n;
    }

    //-------------------------------------
    // other属性のパース（改行=区切り）
    protected function parse_other ( & $ref, $str) {

        foreach (preg_split("!(\r?\n)|\|!",$str) as $sets) {

            if (preg_match('!^(.+?)=(.+)$!',$sets,$match))  {

                $ref[trim($match[1])] =trim($match[2]);

            } elseif (strlen(trim($sets))) {

                $ref =trim($sets);
            }
        }
    }
}