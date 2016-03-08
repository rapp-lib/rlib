<?php 
	
	/**
     * schema.config.php→生成/展開
     */ 
	function rdoc_entry_rapper_deploy ($options=array()) {
            
        // Schemaの初期化
		$r =new Rapper;
        $r->require_mod("app_root");
        $r->apply_filters("init",$options);
        $r->apply_filters("proc",$options);
	}
    
    function rapper_mod_app_root ($r) {
        
        $r->add_filter("init",array(),function($r, $config) {
            
            //$schema_csv_file =registry("Path.webapp_dir")."/config/schema.config.csv";
            $schema_csv_file ="/var/www/vhosts/d.fiar.jp/tpro/data/rapper_test/schema.config.csv";
        	
    		// CSV読み込み
            $loader =new SchemaCsvLoader;
    		$schema =$loader->load_schema_csv($schema_csv_file);

            // Schema:構成定義の初期化
            $schema = & $r->schema($schema);

            $schema =$r->apply_filters("schema",$schema);

            foreach ($schema as $schema_index => & $schema_items) {

                $schema_items =$r->apply_filters("schema.".$schema_index, $schema_items);

                foreach ($schema_items as & $schema_item) {

                    $schema_item =$r->apply_filters("schema_item.".$schema_index, $schema_item);
                }
            }
            
            report("Schemaの初期化完了",$r->schema());

            // Deploy:生成物情報の初期化
            $r->apply_filters("add_deploy");
            
            report("Deployの初期化完了",$r->deploy());
        });
        
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"preview")),function($r, $config) {
            
            report("preview実行",$config);
            
            $deploy = & $r->deploy($config["target"]);
            $r->apply_filters("proc_preview",$deploy);
        });
        
        $r->add_filter("proc",array("cond"=>array("target","mode"=>"download")),function($r, $config) {
            
            report("download実行",$config);
        
            $deploy = & $r->deploy($config["target"]);
            $r->apply_filters("proc_download",$deploy);
        });
        
        $r->require_mod("deploy_doc_input_desc");
        $r->require_mod("deploy_doc_csv");
        
        /*
            $schema_copy =array();
            $schema_copy["table"] =(array)$schema["table"];
            $schema_copy["col"] =(array)$schema["col"];
            $schema_copy["controller"] =(array)$schema["controller"];
            $schema_copy["action"] =(array)$schema["action"];
            $schema_copy["config"] =(array)$schema["config"];
            $schema_copy["model"] =(array)$schema["model"];
            $schema_copy["list_option"] =(array)$schema["list_option"];
            $schema_copy["account"] =(array)$schema["account"];
            $deploy_copy["controller_class"] =(array)$deploy["controller_class"];
            $deploy_copy["page_html"] =(array)$deploy["page_html"];
            $deploy_copy["element_html"] =(array)$deploy["element_html"];
            $deploy_copy["list_option_class"] =(array)$deploy["list_option_class"];
            $deploy_copy["config_file"] =(array)$deploy["config_file"];
            $deploy_copy["doc"] =(array)$deploy["doc"];
            $r->require_mod("basic_load_pages");
            $r->require_mod("basic_model");
            $r->require_mod("basic_list_option");
            $r->require_mod("basic_account");
            $r->require_mod("basic_config_file");
            $r->require_mod("basic_controller");
            $r->require_mod("basic_deploy");
        */
    }
    
    /**
    * 入力仕様書CSVの生成
    */
    function rapper_mod_deploy_doc_input_desc ($r) {
        
        $r->add_filter("add_deploy",array(),function($r) {
            
            $data_callback =function ($r) {
                
                $rows =array(
                    "controller"=>"機能群",
                    "action"=>"画面",
                    "item"=>"要素",
                    "type"=>"入力形式",
                    "required"=>"必須",
                    "length"=>"文字数",
                    "rules"=>"制約",
                );
                $data =array();
                
                foreach ($r->schema("controller") as $c_id => $c) {
                    
                    $data[] =array("controller"=>$c["label"]);
                    
                    // 関連するtableがなければ対象外
                    if ( ! $c["table"]) { continue; }
                    
                    // ログインフォーム
                    if ($c["type"] == "login") {
                        
                        $data[] =array("action"=>"ログインフォーム");
                        $data[] =array(
                            "item"=>"ログインID", 
                            "type"=>"テキスト", 
                            "required"=>"○",
                        );
                        $data[] =array(
                            "item"=>"パスワード", 
                            "type"=>"テキスト", 
                            "required"=>"○",
                        );
                    }
                    
                    // 検索フォーム入力
                    if ($c["type"] == "master" && $c["usage"] != "form") {
                        
                        $data[] =array("action"=>"検索フォーム");
                        $data[] =array(
                            "item"=>"(検索条件)",
                        );
                    }
                    
                    // table形式のフォーム入力
                    if ($c["type"] == "master" && $c["usage"] != "view") {
                        
                        if ($c["usage"] == "form") {
                            
                            $data[] =array("action" =>"フォーム");
                            
                        } else {
                            
                            $data[] =array("action"=>"新規登録/編集フォーム");
                        }
                        
                        // table.relの参照
                        $rel_id =$c["rel"] ? $c["rel"] : "default";
                        
                        foreach ((array)$r->schema("col.".$c["table"]) as $col_id => $col) {
                            
                            // rel=input|requireの項目以外を除外
                            $rel =$col["rels"][$rel_id];
                            
                            // relによる対応付けがない項目は対象外
                            if ($rel != "input" && $rel != "required") { continue; }
                            
                            // 入力形式がない項目は対象外
                            if ( ! $col["type"]) { continue; }
                            
                            // 必須入力
                            $required = ($rel == "required") ? "○" : "";
                            
                            // 要素名
                            $item_label =$col["label"];
                            
                            // 入力形式
                            $input_type_label =$r->schema("input_type.".$col["type"].".label");
                            $input_type_label =$input_type_label ? $input_type_label : $col["type"];
                            
                            // 入力制約
                            $rules_label =array();
                            
                            foreach ((array)$col["rules"] as $rule) {
                                
                                $rule_label =$r->schema("rule.".$rule.".label");
                                $rules_label[] =$rule_label ? $rule_label : $rule;
                            }
                            
                            $rules_label =implode($rules_label,"/");
                            
                            $data[] =array(
                                "item"=>$col["label"],
                                "type"=>$input_type_label,
                                "rules"=>$rules_label,
                                "required"=>$required,
                            );
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
    
    /**
    * CSV形式ドキュメントの生成
    */
    function rapper_mod_deploy_doc_csv ($r) {
		
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