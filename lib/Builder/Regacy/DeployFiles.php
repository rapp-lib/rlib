<?php
namespace R\Lib\Builder\Regacy;

//-------------------------------------
//
class DeployFiles extends OldBuilder {

    protected $tables =array();
    protected $tables_def =array();

    //-------------------------------------
    // Schemaからコード生成
    public function deploy_files ($schema)
    {
        $this->options =array();
        $this->tmp_dir =$tmp_dir =registry("Path.tmp_dir")."/webapp_build/";
        $this->history ="U".date("ymdHis").sprintf("%03d",rand(001,999));

        // Registry_Schema_Tableの補完
        registry($schema);
        $this->fetch_table_schema($schema);
        registry("Schema.tables",false);
        registry("Schema.tables",$this->tables);

        // Registry_Schema_Controllerの補完
        foreach ((array)registry("Schema.controller") as $name => $c) {
            $c["name"] =$name;
            $c["access_as"] = $c["access_as"] ? $c["access_as"] : $c["accessor"];
            $c["priv_required"] = $c["priv_required"] ? $c["priv_required"] : ($c["auth"] ? "true" : "false");
            $c["header"] ='{{inc route="/include/'.$c["access_as"].'_header.html"}}';
            $c["footer"] ='{{inc route="/include/'.$c["access_as"].'_footer.html"}}';
            registry("Schema.controller.".$name,$c);
        }

        builder()->initSchemaFromRegistry();
        builder()->deployAll();
/*
        // Controllerの構築
        foreach ((array)registry("Schema.controller") as $name => $c) {
            $method_name ="build_controller_".$c["type"];
            registry("Schema.controller.".$name,$c);
            $this->$method_name($c);
        }
*/
    }
/*
    //-------------------------------------
    //
    protected function build_controller_master ($c) {

        // テーブル情報参照
        $t =$this->tables[$c["table"]];

        $controller_name = $c["name"];
        $controller = builder()->getSchema()->getController($controller_name);

        // Controllerの構築
        $src =$this->find_skel($c["skel"],
                "master/ProductMasterController.class.php");
        $dest =registry("Path.webapp_dir")
                ."/app/Controller/".str_camelize($c["name"])."Controller.php";
        $this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));

        // HTMLの構築
        foreach ($controller->getAction() as $action) {
            if ( ! $action->getAttr("has_html")) {
                continue;
            }
            $a = $action->getAttr();

            $src =$this->find_skel($c["skel"],
                    "master/product_master.".$action->getName().".html");
            $dest =registry("Path.webapp_dir")
                    ."/html".$action->getPath();
            $this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
        }
    }

    //-------------------------------------
    //
    protected function build_controller_login ($c) {

        // テーブル情報参照
        $t =$this->tables[$c["table"]];

        $controller_name = $c["name"];
        $controller = builder()->getSchema()->getController($controller_name);

        // Controllerの構築
        $src =$this->find_skel($c["skel"],
                "login/MemberLoginController.class.php");
        $dest =registry("Path.webapp_dir")
                ."/app/Controller/".str_camelize($c["name"])."Controller.php";
        $this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));

        // HTMLの構築
        foreach ($controller->getAction() as $action) {
            if ( ! $action->getAttr("has_html")) {
                continue;
            }
            $a = $action->getAttr();

            $src =$this->find_skel($c["skel"],
                    "login/member_login.".$action->getName().".html");
            $dest =registry("Path.webapp_dir")
                    ."/html".$action->getPath();
            $this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
        }
    }

    //-------------------------------------
    //
    protected function build_controller_index ($c) {

        // テーブル情報参照
        $t =$this->tables[$c["table"]];

        $controller_name = $c["name"];
        $controller = builder()->getSchema()->getController($controller_name);

        // Controllerの構築
        $src =$this->find_skel($c["skel"],
                "index/ProductMasterController.class.php");
        $dest =registry("Path.webapp_dir")
                ."/app/Controller/".str_camelize($c["name"])."Controller.php";
        $this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));

        // HTMLの構築
        foreach ($controller->getAction() as $action) {
            if ( ! $action->getAttr("has_html")) {
                continue;
            }
            $a = $action->getAttr();

            $src =$this->find_skel($c["skel"],
                    "index/product_master.".$action->getName().".html");
            $dest =registry("Path.webapp_dir")
                    ."/html".$action->getPath();
            $this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
        }
    }
*/
    //-------------------------------------
    //
    protected function fetch_table_schema () {

        // テーブルごとに処理
        foreach ((array)registry("Schema.tables") as $t_name => $t) {

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

            $this->tables[$t_name] =$t;
        }

        // DB初期化SQL構築
        foreach ((array)$this->tables as $t_name => $t) {

            $t_def =& $this->tables_def[$t_name];
            $t_def =(array)$t["def"];
            $t_def["table"] =$t_name;
            $t_def["pkey"] =preg_replace(
                    '!^'.preg_quote($t_name).'\.!',
                    '', $t["pkey"]);

            foreach ((array)$t["cols"] as $tc_name => $tc) {

                $tc_name =preg_replace('!^'.preg_quote($t_name).'\.!', '', $tc_name);

                $tc_def =& $this->tables_def[$t_name]["cols"][$tc_name];
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

        report("Fetched table-schema.",array(
            "tables" =>$this->tables,
            "tables_def" =>$this->tables_def,
        ));
    }

    //-------------------------------------
    // fieldsを用途ごとにフィルタリングする
    // $type: search sort input save list detail csv
    public function filter_fields ($fields, $type) {
        $fields = (array)$fields;
        foreach ($fields as $tc_name => $tc) {

            if ($type == "search"
                    && ($tc["type"] != "textarea"
                    && $tc["type"] != "text")) {

                unset($fields[$tc_name]);
            }

            if ($type == "sort"
                    && ($tc["type"] == "textarea"
                    || $tc["type"] == "file"
                    || $tc["type"] == "password")) {

                unset($fields[$tc_name]);
            }

            if ($type == "list"
                    && ($tc["type"] == "textarea"
                    || $tc["type"] == "file"
                    || $tc["type"] == "password")) {

                unset($fields[$tc_name]);
            }

            if ($type == "save"
                    && (false)) {

                unset($fields[$tc_name]);
            }
        }

        if ($type == "search" || $type == "sort") {

            $fields =array_slice($fields,0,3);
        }

        if ($type == "list") {

            $fields =array_slice($fields,0,6);
        }

        return $fields;
    }
/*
    //-------------------------------------
    // テンプレートファイルの検索
    public function find_skel ($skel_name, $target_file) {

        if ($found =find_include_path("modules/webapp_skel_".$skel_name."/".$target_file)) {

            return $found;
        }

        return find_include_path("modules/webapp_skel/".$target_file);
    }
*/
}
