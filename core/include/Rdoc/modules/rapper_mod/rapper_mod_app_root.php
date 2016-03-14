<?php
    
	/**
     * 起動用Mod
     */ 
    function rapper_mod_app_root ($r) {
        
        // Schema CSVファイルの読み込み（サーバ上から読み込み）
        $r->add_filter("init",array("cond"=>array("schema_csv_file"=>"config")),function($r, $config) {
            
            $schema_csv_file =registry("Path.webapp_dir")."/config/schema.config.csv";
            //$schema_csv_file ="/var/www/vhosts/d.fiar.jp/tpro/data/rapper_test/schema.config.csv";
            
            // CSV読み込み
            $schema =$r->parse_schema_csv_file($schema_csv_file);
            $r->schema($schema);
        });
        
        // Schema CSVファイルの読み込み（アップロード）
        $r->add_filter("init",array("cond"=>array("schema_csv_file"=>"upload")),function($r, $config) {
            
            $schema_csv_file =$_FILES["schema_csv_file"]["tmp_name"];
        	
    		// CSV読み込み
            $schema =$r->parse_schema_csv_file($schema_csv_file);
            $r->schema($schema);
        });
            
        // Modの読み込み→Schema/Deployの各種初期化フィルタの呼び出し
        $r->add_filter("init",array(),function($r, $config) {
            
            $schema = & $r->schema();
            
            // modの読み込み
            foreach ((array)$schema["mod"] as $mod_id => $mod) {
                
                $r->require_mod($mod_id);
            }
            
            $schema =$r->apply_filters("init.schema",$schema);

            foreach ($schema as $schema_index => & $schema_items) {

                foreach ($schema_items as & $schema_item) {

                    $schema_item =$r->apply_filters("init.schema.".$schema_index, $schema_item);
                }
            }
            
            report("Schemaの初期化完了",$r->schema());

            // Deploy:生成物情報の初期化
            $r->apply_filters("init.deploy");
            
            report("Deployの初期化完了",$r->deploy());
        });
        
        // テストの実行
        $r->add_filter("proc",array("cond"=>array("mode"=>"test")),function($r, $config) {
            
            report("test実行",$config);
            
            $schema = & $r->schema();
            $deploy = & $r->deploy();
            
            $r->apply_filters("proc_test.schema",$schema);
            $r->apply_filters("proc_test.deploy",$deploy);
        });
        
        // 対象を指定したプレビュー表示の実行
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"preview")),function($r, $config) {
            
            report("preview実行",$config);
            
            $deploy = & $r->deploy($config["target"]);
            $r->apply_filters("proc_preview",$deploy);
        });
        
        // 対象を指定したダウンロードの実行
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"download")),function($r, $config) {
            
            report("download実行",$config);
        
            $deploy = & $r->deploy($config["target"]);
            $r->apply_filters("proc_download",$deploy);
        });
        
        // controller.*のtypeによるActionの補完
        $r->require_mod("init_controller_master");
        $r->require_mod("init_controller_login");
        
        // テスト/入力仕様書のDeploy登録
        $r->require_mod("deploy_doc_test");
        $r->require_mod("deploy_doc_input_desc");
        
        // CSV形式の仕様書Deployの出力処理
        $r->require_mod("proc_doc_csv");
        
        /*
            routing,label,auth,install_sql
                ->[config_file]
            [config_file.*]
                ->config/xxx.config.php
        */
        $r->require_mod("init_config_file");
        
        /*
            [table.*]
                ->[model]
            [model.*]
                ->app/model/XxxModel.class.php
        */
        $r->require_mod("init_model");
        
        /*
            [col.*]
                ->[list_option]
            [list_option]
                ->app/list/XxxListOptions.class.php
        */
        $r->require_mod("init_list_option");
        
        /*
            [controller.*]
                ->[account]
            [account.*]
                app/context/XxxContext.class.php
        */
        $r->require_mod("init_account");
        
        /*
            [controller.*]
                ->[wrapper]
            [controller.*]
                ->app/controller/XxxController.class.php
                    class XxxController
                    [action.*]
                        act_xxx()
            [action.*]
                ->html/xxx/xxx.xxx.html
            [wrapper.*]
                ->html/element/xxx_wrapper_head.html
                ->html/element/xxx_wrapper_foot.html
        */
        $r->require_mod("init_account");
        
        // proc(data_type=php_tmpl)
        $r->require_mod("proc_php_tmpl");
    }

    /**
    * コンフィグファイルに関するschema/deployの生成
    */
    function rapper_mod_init_config_file ($r) {
    }

    /**
    * Modelに関するschema/deployの生成
    */
    function rapper_mod_init_model ($r) {
    }

    /**
    * ListOptionに関するschema/deployの生成
    */
    function rapper_mod_init_list_option ($r) {
    }

    /**
    * Accountに関するschema/deployの生成
    */
    function rapper_mod_init_account ($r) {
    }

    /**
    * Controllerに関するschema/deployの生成
    */
    function rapper_mod_init_controller ($r) {
    }

    /**
    * deploy(data_type=php_tmpl)に対する処理
    */
    function rapper_mod_proc_php_tmpl ($r) {
    }
    