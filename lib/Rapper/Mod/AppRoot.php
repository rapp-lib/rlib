<?php

namespace R\Lib\Rapper\Mod;
use R\Lib\Rapper\SchemaCsvParser;

/**
 * 起動用Mod
 */
class AppRoot extends BaseMod
{
    /**
     *
     */
    public function install ()
    {
        $this->installSchemaCsvLoader();
        $this->installApplyFilters();
        $this->installProc();
        $this->installMod();
    }

    /**
     *
     */
    private function installSchemaCsvLoader ()
    {
        $r =$this->r;

        // Schema CSVファイルの読み込み（サーバ上から読み込み）
        $r->add_filter("init",array("cond"=>array("schema_csv_file"=>"config")),function($r, $config)
        {
            $schema_csv_file =registry("Path.webapp_dir")."/config/schema.config.csv";

            // CSV読み込み
            $schema =SchemaCsvParser::parse($schema_csv_file);
            $r->schema($schema);
        });

        // Schema CSVファイルの読み込み（アップロード）
        $r->add_filter("init",array("cond"=>array("schema_csv_file"=>"upload")),function($r, $config)
        {
            $schema_csv_file =$_FILES["schema_csv_file"]["tmp_name"];

            // CSV読み込み
            $schema =SchemaCsvParser::parse($schema_csv_file);
            $r->schema($schema);
        });
    }

    /**
     *
     */
    private function installApplyFilters ()
    {
        $r =$this->r;

        // Modの読み込み→Schema/Deployの各種初期化フィルタの呼び出し
        $r->add_filter("init",array(),function($r, $config)
        {
            $schema = & $r->schema();

            // modの読み込み
            foreach ((array)$schema["mod"] as $mod_id => $mod) {
                $r->require_mod($mod_id);
            }

            // schema情報の初期化
            $schema =$r->apply_filters("init.schema",$schema);

            foreach ($schema as $si => & $schema_item) {
                $schema_item = $r->apply_filters("init.schema.".$si, $schema_item);
            }

            report("Schemaの初期化完了",$r->schema());

            // deploy情報の初期化
            $r->apply_filters("init.deploy");

            foreach ($schema as $si => $schema_item) {
                $r->apply_filters("init.deploy.".$si, $r->factorySchemaObject($si, $schema_item));
            }

            report("Deployの初期化完了",$r->deploy());
        });
    }

    /**
     *
     */
    private function installProc ()
    {
        $r =$this->r;

        // テストの実行
        $r->add_filter("proc",array("cond"=>array("mode"=>"test")),function($r, $config)
        {
            report("test実行",$config);

            $ds = & $r->deploy();

            foreach ($ds as $d) {
                report($d);
                $d =$r->apply_filters("proc.preview.deploy",$d);
                print $d["preview"];
            }
        });

        // 対象を指定したプレビュー表示の実行
        $r->add_filter("proc",array("cond"=>array("mode"=>"preview")),function($r, $config)
        {
            report("preview実行",$config);

            $d = & $r->deploy($config["target"]);
            $r->apply_filters("proc.preview.deploy",$d);

            print $d["preview"];
        });

        // 対象を指定したダウンロードの実行
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"download")),function($r, $config)
        {
            report("download実行",$config);

            $deploy = & $r->deploy($config["target"]);
            $deploy =$r->apply_filters("proc.src.deploy",$deploy);

            clean_output_shutdown(array(
                "download" =>basename($deploy["dest_file"]),
                "data" =>$deploy["src"],
            ));
        });
    }

    /**
     *
     */
    private function installMod ()
    {
        $r =$this->r;

        // テスト仕様書のDeploy登録
        /*
            init.deploy
                ->{id=docs.test ,dest_file=/docs/test.csv, data_type=doc_csv}
        */
        $r->require_mod("deploy_doc_test");

        // 入力仕様書のDeploy登録
        /*
            init.deploy
                ->{id=docs.input_desc ,dest_file=/docs/input_desc.csv, data_type=doc_csv}
        */
        $r->require_mod("deploy_doc_input_desc");

        // CSV形式の仕様書Deployの出力処理
        /*
            proc deploy(data_type=doc_csv)(mode=["preview","download"])
        */
        $r->require_mod("proc_doc_csv");

        /*
            init.schema ["routing","label","auth","install_sql"]
                ->[config_file]
            init.deploy [config_file]
                ->{id=config_file.xxx ,dest_file=/config/xxx.config.php, data_type=php_tmpl}
        */
        $r->require_mod("init_config_file");

        /*
            init.schema [table]
                ->[model]
            init.deploy [model]
                ->/app/model/XxxModel.class.php
        */
        $r->require_mod("init_model");

        /*

        */
        $r->require_mod("init_list_option");

        /*
            init.schema [controller]
                ->[account]
            init.deploy [account.*]
                app/context/XxxContext.class.php
        */
        $r->require_mod("init_account");

        /*
            init.schema [controller]
                ->[wrapper]
            init.deploy [wrapper.*]
                ->html/element/xxx_wrapper_head.html
                ->html/element/xxx_wrapper_foot.html
        */
        $r->require_mod("init_wrapper");

        /*
            init.deploy [action]
                ->{id=action_method.xxx.xxx, method_name=act_xxx()}
            init.deploy [action.*]
                ->html/xxx/xxx.xxx.html
            init.deploy [controller]
                ->app/controller/XxxController.class.php
                    class XxxController
                    proc.deploy [action]
        */
        $r->require_mod("init_controller");

        // phpテンプレートのDeployを処理
        /*
            proc deploy(data_type=php_tmpl)(mode=["preview","download"])
        */
        $r->require_mod("proc_php_tmpl");
    }
}