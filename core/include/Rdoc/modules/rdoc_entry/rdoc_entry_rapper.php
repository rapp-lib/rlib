<?php 
	
	/**
     * schema.config.csv→生成/展開
     */ 
	function rdoc_entry_rapper ($options=array()) {
            
        // Schemaの初期化
		$r =new Rapper;
        $r->require_mod("app_root");
        $r->apply_filters("init",$options);
        $r->apply_filters("proc",$options);
	}
    
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
        
        $r->require_mod("init_controller_master");
        $r->require_mod("init_controller_login");
    }
    
    /**
    * CSV形式ドキュメントの処理
    */
    function rapper_mod_proc_doc_csv ($r) {
		
        // ダウンロード処理
        $r->add_filter("proc_download",array("cond"=>array("data_type"=>"doc_csv")),function($r, $deploy) {
            
            $dest_file =$deploy["dest_file"];
            
            $deploy_data =$deploy["data_callback"]($r);
            $rows =$deploy_data["rows"];
            $data =$deploy_data["data"];
            
			$csv =new CSVHandler("php://memory", "w", array(
				"rows" =>$rows,
			));
            $csv->write_lines($data);
    		$fp =$csv->get_file_handle();
            rewind($fp);
            $csv_str =stream_get_contents($fp);
            fclose($fp);
            
            clean_output_shutdown(array(
				"download" =>basename($dest_file),
				"data" =>$csv_str,
			));
		});
        
        // プレビュー処理
        $r->add_filter("proc_preview",array("cond"=>array("data_type"=>"doc_csv")),function($r, $deploy) {
            
            $deploy_data =$deploy["data_callback"]($r);
            $rows =$deploy_data["rows"];
            $data =$deploy_data["data"];
            
    		$html ='<table style="border:black 1px solid;">';
    		$html .='<tr style="background-locor:glay;">';
    		$html .='<td>[line]</td>';
    		
    		foreach ($rows as $k=>$v) {
                
    			$html .='<td>['.$v.']</td>';
    		}
    		
    		$html .='</tr>';
    		
    		foreach ($data as $n => $line) {
    			
    			$html .='<tr>';
    			$html .='<td>['.sprintf('%04d',$n+1).']</td>';
    			
    			foreach ($rows as $k=>$v) {
                    
    				$html .='<td>'.$line[$k].'</td>';
    			}
    			
    			$html .='</tr>';
    		}
    		
    		$html .='</table>';
            
            print $html;
		});
	}
    
    /**
    * master系ControllerのSchema/Deploy生成
    */
    function rapper_mod_init_controller_master ($r) {
    
        // schema.actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"master")),function($r,$c) {
            
            if ($r->schema("action.".$c["_id"])) { continue; }
            
            // 検索フォーム入力
            if ($c["usage"] != "form") {
                
                $r->schema("action.".$c["_id"].".view_list", array(
                    "action" =>"view_list",
                    "_id" =>$c["_id"].".view_list",
                    "label" =>"一覧表示",
                    "type" =>"master.view_list",
                ));
            }
            
            // table形式のフォーム入力
            if ($c["usage"] != "view") {
                    
                $r->schema("action.".$c["_id"].".entry_form",array(
                    "action" =>"entry_form",
                    "_id" =>$c["_id"].".entry_form",
                    "label" =>"フォーム",
                    "type" =>"master.entry_form",
                ));
                $r->schema("action.".$c["_id"].".entry_confirm",array(
                    "action" =>"entry_confirm",
                    "_id" =>$c["_id"].".entry_confirm",
                    "label" =>"確認",
                    "type" =>"master.entry_confirm",
                ));
                $r->schema("action.".$c["_id"].".entry_exec",array(
                    "action" =>"entry_exec",
                    "_id" =>$c["_id"].".entry_exec",
                    "label" =>"完了",
                    "type" =>"master.entry_exec",
                ));
            }
            
            // 削除
            if ($c["usage"] != "form" && $c["usage"] != "view") {
                
                $r->schema("action.".$c["_id"].".delete_confirm",array(
                    "action" =>"delete_confirm",
                    "_id" =>$c["_id"].".delete_confirm",
                    "label" =>"削除",
                    "type" =>"master.delete_confirm",
                ));
                $r->schema("action.".$c["_id"].".delete_exec", array(
                    "action" =>"delete_exec",
                    "_id" =>$c["_id"].".delete_exec",
                    "label" =>"削除完了",
                    "type" =>"master.delete_exec",
                ));
            }
        });
    }
    
    /**
    * login系ControllerのSchema/Deploy生成
    */
    function rapper_mod_init_controller_login ($r) {
    
        // schema.actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"login")),function($r,$c) {
            
            if ($r->schema("action.".$c["_id"])) { continue; }
            
            $r->schema("action.".$c["_id"].".entry_form", array(
                "action" =>"entry_form",
                "_id" =>$c["_id"].".entry_form",
                "label" =>"ログイン",
                "type" =>"login.entry_form",
            ));
            $r->schema("action.".$c["_id"].".logout",array(
                "action" =>"logout",
                "_id" =>$c["_id"].".logout",
                "label" =>"ログアウト",
                "type" =>"login.logout",
            ));
        });
    }
    
    /**
    * 入力仕様書CSVの生成
    */
    function rapper_mod_deploy_doc_input_desc ($r) {
        
        $r->require_mod("proc_doc_csv");
        
        $r->add_filter("init.deploy",array(),function($r) {
            
            $data_callback =function ($r) {
                
                $rows =array(
                    "wrapper"=>"大分類",
                    "controller"=>"小分類",
                    "action"=>"画面",
                    "type"=>"テスト種別",
                    "item"=>"対象要素",
        			"cond" =>"前提条件",
        			"trigger" =>"操作/トリガー",
        			"expect" =>"期待値",
        			"etc" =>"備考",
        			"test_date" =>"テスト実施日時",
        			"test_result" =>"テスト結果",
        			"test_etc" =>"テスト結果備考",
                );
                $data =array();
                
                foreach ($r->schema("controller") as $c) {
                    
                    $data[] =array("controller"=>$r->label("controller",$c["_id"]));
                    
                    if ($c["auth"]) {
                        
                        $data[] =array(
                            "type"=>"認証",
                            "item"=>"",
                            "cond" =>$r->label("user",$c["auth"])."でログインしていない状態",
                            "trigger" =>"各画面へのアクセス",
                            "expect" =>"ログイン画面へ転送される",
                        );
                    }
                    
                    foreach ((array)$r->schema("action.".$c["_id"]) as $a) {
                        
                        $data[] =array(
                            "action"=>$r->label("action",$a["_id"]), 
                            "action_id"=>$a["_id"],
                        );
                        
                        // ログインフォーム
                        if ($a["type"] == "login.entry_form") {
                            
                            $data[] =array(
                                "type"=>"入力",
                                "item"=>"ログインID",
                                "cond" =>"",
                                "trigger" =>"",
                                "expect" =>$r->label("input_type","text")."として入力可能",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"ログインID",
                                "cond" =>"入力しない",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                            $data[] =array(
                                "type"=>"入力",
                                "item"=>"パスワード",
                                "cond" =>"",
                                "trigger" =>"",
                                "expect" =>$r->label("input_type","text")."として入力可能",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"パスワード",
                                "cond" =>"入力しない",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"パスワード",
                                "cond" =>"ログインIDと誤った組み合わせで入力",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                        }
                        
                        // 検索フォーム入力
                        if ($a["type"] == "master.view_list") {
                            
                        }
                        
                        // table形式のフォーム入力
                        if ($a["type"] == "master.entry_form") {
                            
                            $table =$a["table"] ? $a["table"] : ($c["table"] ? $c["table"] : "");
                            $rel_id =$a["rel"] ? $a["rel"] : ($c["rel"] ? $c["rel"] : "default");
                            
                            // 関連するtableがなければ対象外
                            if ( ! $table) { continue; }
                            
                            foreach ((array)$r->schema("col.".$table) as $col_id => $col) {
                                
                                // 関係する入力項目（rel=input|required）以外を除外
                                $rel =$col["rels"][$rel_id];
                                if ($rel != "input" && $rel != "required") { continue; }
                                
                                $data[] =array(
                                    "type"=>"入力",
                                    "item"=>$r->label("col",$col["_id"]),
                                    "cond" =>"",
                                    "trigger" =>"",
                                    "expect" =>$r->label("input_type",$col["type"])."として入力可能",
                                );
                                
                                if ($rel == "required") {
                                    
                                    $data[] =array(
                                        "type"=>"入力エラー",
                                        "item"=>$r->label("col",$col["_id"]),
                                        "cond" =>"入力しない",
                                        "trigger" =>"フォーム送信",
                                        "expect" =>"エラーとなる",
                                    );
                                }
                                
                                foreach ((array)$col["rules"] as $rule) {
                                    
                                    $data[] =array(
                                        "type"=>"入力エラー",
                                        "item"=>$r->label("col",$col["_id"]),
                                        "cond" =>$r->label("rule",$rule)."に対して不正な形式で入力",
                                        "trigger" =>"フォーム送信",
                                        "expect" =>"エラーとなる",
                                    );
                                }
                            }
                            
                            $data[] =array(
                                "type"=>"遷移",
                                "item"=>"",
                                "cond" =>"入力エラーがない状態",
                                "trigger" =>"フォーム送信",
                                "expect" =>"確認画面に遷移",
                            );
                        }
                    }
                }
                
                return array(
                    "rows"=>$rows,
                    "data"=>$data,
                );
            };
            
            $r->deploy("doc.test",array(
                "dest_file" =>"docs/test.csv",
                "data_type" =>"doc_csv",
                "data_callback" =>$data_callback,
            ));
        });
    }
    
    /**
    * テスト仕様書CSVの生成
    */
    function rapper_mod_deploy_doc_test ($r) {
        
        $r->require_mod("proc_doc_csv");
        
        $r->add_filter("init.deploy",array(),function($r) {
            
            $data_callback =function ($r) {
                
                $rows =array(
                    "controller"=>"機能群",
                    "action"=>"画面",
                    "action_id"=>"画面ID",
                    "item"=>"要素",
                    "type"=>"入力形式",
                    "required"=>"必須",
                    "length"=>"文字数",
                    "rules"=>"制約",
                );
                $data =array();
                
                foreach ($r->schema("controller") as $c) {
                    
                    $data[] =array(
                        "wrapper"=>$r->label("wrapper",$c["wrapper"]),
                        "controller"=>$r->label("controller",$c["_id"]),
                    );
                    
                    foreach ($r->schema("action.".$c["_id"]) as $a) {
                        
                        $data[] =array(
                            "action"=>$r->label("action",$a["_id"]), 
                            "action_id"=>$a["_id"],
                        );
                            
                        // ログインフォーム
                        if ($a["type"] == "login.entry_form") {
                            
                            $data[] =array(
                                "item"=>"ログインID",
                                "type"=>$r->label("input_type","text"),
                                "required"=>"○",
                            );
                            $data[] =array(
                                "item"=>"パスワード",
                                "type"=>$r->label("input_type","text"),
                                "required"=>"○",
                            );
                        }
                        
                        // 検索フォーム入力
                        if ($a["type"] == "master.view_list") {
                            
                            $data[] =array(
                                "item"=>"(検索条件)",
                            );
                        }
                        
                        // table形式のフォーム入力
                        if ($a["type"] == "master.entry_form") {
                            
                            $table =$a["table"] ? $a["table"] : ($c["table"] ? $c["table"] : "");
                            $rel_id =$a["rel"] ? $a["rel"] : ($c["rel"] ? $c["rel"] : "default");
                            
                            // 関連するtableがなければ対象外
                            if ( ! $table) { continue; }
                            
                            foreach ((array)$r->schema("col.".$table) as $col_id => $col) {
                                
                                // 関係する入力項目（rel=input|required）以外を除外
                                $rel =$col["rels"][$rel_id];
                                if ($rel != "input" && $rel != "required") { continue; }
                                
                                $data[] =array(
                                    "item"=>$r->label("col",$col["_id"]),
                                    "type"=>$r->label("input_type",$col["type"]),
                                    "rules"=>implode(",", (array)$r->label("rule",$col["rules"])),
                                    "required"=>($rel == "required") ? "○" : "",
                                );
                            }
                        }
                    }
                }
                
                return array(
                    "rows"=>$rows,
                    "data"=>$data,
                );
            };
            
            $r->deploy("doc.input_desc",array(
                "dest_file" =>"docs/input_desc.csv",
                "data_type" =>"doc_csv",
                "data_callback" =>$data_callback,
            ));
        });
    }