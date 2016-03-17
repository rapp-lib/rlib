<?php
    
	/**
     * 起動用Mod
     */ 
    function rapper_mod_app_root ($r) {
        
        // Schema CSVファイルの読み込み（サーバ上から読み込み）
        $r->add_filter("init",array("cond"=>array("schema_csv_file"=>"config")),function($r, $config) {
            
            //$schema_csv_file =registry("Path.webapp_dir")."/config/schema.config.csv";
            $schema_csv_file ="/var/www/vhosts/d.fiar.jp/tpro/data/rapper_test/schema.config.csv";
            
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
            
            // 再帰的にapply_filtersを行う処理
            $f =function ($filter, & $schema_item, $parents, $overwrite=true) use ( & $f, $r) {
                
                if ($parents) {
                    
                    $parent =array_shift($parents);
                    
                    foreach ($schema_item as & $schema_item_elm) {
                        
                        $f($filter, $schema_item_elm, $parents, $overwrite);
                    }
                
                } else {
                
                    foreach ($schema_item as & $schema_item_elm) {
                        
                        if ($overwrite) {
                            
                            $schema_item_elm =$r->apply_filters($filter, $schema_item_elm);
                        
                        } else {
                            
                            $r->apply_filters($filter, $schema_item_elm);
                        }
                    }    
                }
            };
            
            // schema情報の初期化
            $schema =$r->apply_filters("init.schema",$schema);
            
            foreach ($schema as $si => & $schema_item) {
                
                $f("pre_init.schema.".$si,$schema_item,$schema["schema"][$si]["parents"],true);
            }
            
            foreach ($schema as $si => & $schema_item) {
                
                $f("init.schema.".$si,$schema_item,$schema["schema"][$si]["parents"],true);
            }
            
            foreach ($schema as $si => & $schema_item) {
                
                $f("post_init.schema.".$si,$schema_item,$schema["schema"][$si]["parents"],true);
            }
            
            report("Schemaの初期化完了",$r->schema());

            // deploy（生成物）情報の初期化
            $r->apply_filters("init.deploy");
            
            foreach ($schema as $si => & $schema_item) {
                
                $f("init.deploy.".$si,$schema_item,$schema["schema"][$si]["parents"],false);
            }
            
            report("Deployの初期化完了",$r->deploy());
        });
        
        // テストの実行
        $r->add_filter("proc",array("cond"=>array("mode"=>"test")),function($r, $config) {
            
            report("test実行",$config);
            
            $ds = & $r->deploy();
            
            foreach ($ds as $d) {
                
                $r->apply_filters("proc.preview.deploy",$d);
            }
        });
        
        // 対象を指定したプレビュー表示の実行
        $r->add_filter("proc",array("cond"=>array("mode"=>"preview")),function($r, $config) {
            
            report("preview実行",$config);
            
            $d = & $r->deploy($config["target"]);
            $r->apply_filters("proc.preview.deploy",$d);
        });
        
        // 対象を指定したダウンロードの実行
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"download")),function($r, $config) {
            
            report("download実行",$config);
        
            $deploy = & $r->deploy($config["target"]);
            $deploy =$r->apply_filters("proc.src.deploy",$deploy);
            
            clean_output_shutdown(array(
				"download" =>basename($deploy["dest_file"]),
				"data" =>$deploy["src"],
			));
        });
        
        // controller.*のtypeによるActionの補完
        /*
            init.schema [controller](type=["master","login"])
                ->[action]
        */
        $r->require_mod("init_controller_master");
        $r->require_mod("init_controller_login");
        
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
            init.schema [col]
                ->[list_option]
            init.deploy [list_option]
                ->app/list/XxxListOptions.class.php
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

    /**
    * コンフィグファイルに関するschema/deployの生成
    */
    function rapper_mod_init_config_file ($r) {
        
        // init.schema ["routing","label","auth","install_sql"]
        // ->[config_file]
        $r->add_filter("init.schema",array(),function($r, $s) {
            
            $s["config_file"]["routing"] =array("_id" =>"routing_config", "file" =>"routing.config.php");
            $s["config_file"]["label"] =array("_id" =>"label_config", "file" =>"label.config.php");
            $s["config_file"]["auth"] =array("_id" =>"auth_config", "file" =>"auth.config.php");
            $s["config_file"]["install_sql"] =array("_id" =>"install_sql", "file" =>"install.sql");
            
            return $s;
        });
        
        // init.deploy [config_file]
        // ->{id=config_file.xxx ,dest_file=/config/xxx.config.php, data_type=php_tmpl}
        $r->add_filter("init.deploy.config_file",array(),function($r, $config_file) {
            
            $r->deploy("config_file.".$config_file["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"config_file/".$config_file["file"],
                "dest_file" =>"app/config/".$config_file["file"],
            ));
        });
    }

    /**
    * Modelに関するschema/deployの生成
    */
    function rapper_mod_init_model ($r) {
        
        // init.schema [table]
        // ->[model]
        $r->add_filter("init.schema.table",array(),function($r, $t) {
            
            if ($t["nomodel"]) { return; }
            
            $_id =$t["_id"];
            $r->schema("model.".$_id,array(
                "_id" =>$_id,
                "table" =>$t["_id"],
            ));
        });
        
        // init.deploy [model]
        // ->/app/model/XxxModel.class.php
        $r->add_filter("init.deploy.model",array(),function($r, $model) {
            
            $r->deploy("model.".$model["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"model/ProductModel.class.php",
                "dest_file" =>"app/model/".$model["_id"]."Model.class.php",
            ));
        });
    }

    /**
    * ListOptionに関するschema/deployの生成
    */
    function rapper_mod_init_list_option ($r) {
        
        // init.schema [col]
        // ->[list_option]
        $r->add_filter("init.schema.col",array("cond"=>array("list")),function($r, $col) {
            
            $_id =$col["list"];
            $r->schema("list_option.".$_id,array(
                "_id" =>$_id,
            ));
        });
        
        // init.deploy [list_option]
        // ->app/list/XxxList.class.php
        $r->add_filter("init.deploy.list_option",array(),function($r, $list_option) {
            
            $r->deploy("list_option.".$list_option["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"list/CategoryList.class.php",
                "dest_file" =>"app/list/".str_camelize($list_option["_id"])."List.class.php",
            ));
        });
    }

    /**
    * Accountに関するschema/deployの生成
    */
    function rapper_mod_init_account ($r) {
        
        // init.schema [controller]
        // ->[account]
        $r->add_filter("init.schema.controller",array("cond"=>array("auth")),function($r, $c) {
            
            $_id =$c["auth"];
            $r->schema("account.".$_id,array(
                "_id" =>$_id,
            ));
        });
        
        // init.deploy [account.*]
        // ->app/context/XxxContext.class.php
        $r->add_filter("init.deploy.account",array(),function($r, $account) {
            
            $r->deploy("auth_context.".$account["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"context/AdminAuthContext.class.php",
                "dest_file" =>"app/context/".str_camelize($account["_id"])."AuthContext.class.php",
            ));
        });
    }

    /**
    * Wrapperに関するschema/deployの生成
    */
    function rapper_mod_init_wrapper ($r) {
        
        // init.schema [controller]
        // ->[wrapper]
        $r->add_filter("init.schema.controller",array("cond"=>array("wrapper")),function($r, $c) {
            
            $_id =$c["wrapper"];
            $r->schema("wrapper.".$_id,array(
                "_id" =>$_id,
            ));
        });
        
        // init.deploy [wrapper.*]
        // ->html/element/xxx_wrapper_head.html
        // ->html/element/xxx_wrapper_foot.html
        $r->add_filter("init.deploy.wrapper",array(),function($r, $wrapper) {
            
            $r->deploy("wrapper.".$wrapper["_id"]."_header",array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"html/element/default_header.html",
                "dest_file" =>"html/element/".$wrapper["_id"]."_header.html",
            ));
            $r->deploy("wrapper.".$wrapper["_id"]."_footer",array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"html/element/default_footer.html",
                "dest_file" =>"html/element/".$wrapper["_id"]."_footer.html",
            ));
        });
        
    }

    /**
    * Controllerに関するschema/deployの生成
    */
    function rapper_mod_init_controller ($r) {
        
        $r->register_method("get_fields",function ($r, $c_id, $type){
            
            $c =$r->schema("controller.".$c_id);
            
            $table =$a["table"] ? $a["table"] : ($c["table"] ? $c["table"] : "");
            $rel_id =$a["rel"] ? $a["rel"] : ($c["rel"] ? $c["rel"] : "default");
            
            // 関連するtableがなければ対象外
            if ( ! $table) { continue; }
            
            foreach ((array)$r->schema("col.".$table) as $col_id => $col) {
                // 関係する入力項目（rel=input|required）以外を除外
                $rel =$col["rels"][$rel_id];
                if ($rel != "input" && $rel != "required") { continue; }
            }
        });
    
        // 親子関係のあるSchemaの定義補完
        $r->add_filter("init.schema",array(),function($r, $schema) {
            
            $schema["schema"]["action"]["parents"] =array("controller");
            return $schema;
        });
        
        // init.deploy [action]
        // ->{id=action_method.xxx.xxx, method_name=act_xxx()}
        // ->html/xxx/xxx.xxx.html
        $r->add_filter("init.deploy.action",array(),function($r, $a) {
            
            $_id =$a["_id"];
            $r->deploy("action_html.".$_id,array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"html/product_master/product_master.".$a["type"].".html",
                "dest_file" =>"html/".$a["path"],
            ));
            $r->deploy("action_method.".$_id,array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"controller/ProductMaster/".$a["type"].".php",
            ));
        });
        
        // init.deploy [controller]
        // ->app/controller/XxxController.class.php
        //   class XxxController
        //   proc.deploy [action]
        $r->add_filter("init.deploy.controller",array(),function($r, $c) {
            
            $r->deploy("controller_class.".$c["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"controller/XxxController.class.php",
                "dest_file" =>"app/controller/".str_camelize($c["_id"])."Controller.class.php",
                "tmpl_schema" =>array(
                    "c" =>"controller.".$c["_id"],
                ),
            ));
        });
    }

    /**
    * deploy(data_type=php_tmpl)に対する処理
    */
    function rapper_mod_proc_php_tmpl ($r) {
        
        $r->add_filter("proc.preview.deploy",array("cond"=>array("data_type"=>"php_tmpl")),function($r, $deploy) {
            
            // tmpl_vars/tmpl_schema_varsのアサイン
            $tmpl_vars =(array)$deploy["tmpl_vars"];
            
            foreach ((array)$deploy["tmpl_schema"] as $k => $v) {
                
                $tmpl_vars[$k] =$r->schema($v);
            }
            
            // tmpl_fileの検索
            $src =$r->parse_php_tmpl($deploy["tmpl_file"],$tmpl_vars);
            
            $deploy["preview"] ='<code>'.$src.'</code>';
            
            return $deploy;
        });
        
        $r->add_filter("proc.src.deploy",array("cond"=>array("data_type"=>"php_tmpl")),function($r, $deploy) {
            
            // tmpl_vars/tmpl_schema_varsのアサイン
            $tmpl_vars =(array)$deploy["tmpl_vars"];
            
            foreach ((array)$deploy["tmpl_schema"] as $k => $v) {
                
                $tmpl_vars[$k] =$r->schema($v);
            }
            
            // tmpl_fileの検索
            $src =$r->parse_php_tmpl($deploy["tmpl_file"],$tmpl_vars);
            
            $deploy["preview"] ='<code>'.$src.'</code>';
            
            return $deploy;
        });
        
    }
    