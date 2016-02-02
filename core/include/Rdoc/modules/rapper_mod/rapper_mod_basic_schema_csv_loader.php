<?php

    /**
     *
     */
    function rapper_mod_basic_schema_csv_loader ($r) {
        
        $r->add_filter("load_schema",function ($r, $schema) {
            
    		$src_file =registry("Path.webapp_dir")."/config/schema.config.csv";
    		
    		if ( ! file_exists($src_file)) {
    		
    			report_error("src_file is-not exists",array(
    				"src_file" =>$src_file,
    			));
    		}
    		
    		$data =$this->load_schema_csv($src_file);
    		$this->deploy_src(registry("Path.webapp_dir")."/config/_schema.config.php", $data);
        });
    }
	
	//-------------------------------------
	// SchemaConfigPHP：SchemaCSVファイルを読み込んで、SchemaConfigのPHPを生成する
	function load_schema_csv ($filename) {
	
		$csv =new CSVHandler($filename,"r",array(
			"file_charset" =>"SJIS-WIN",
		));
		
		// 読み込みモード/切り替え行
		$re ="";
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
				
				$re =$current_line[0];
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
			if ($re == "#tables" && strlen($current_data["table"])) {
				
				$parent_data =$current_data;
				$ref =& $s["Schema.tables.".$current_data["table"]];
				
			// #tables:col行
			} elseif ($re == "#tables" && $parent_data["table"] && strlen($current_data["col"])) {
				
				$ref =& $s["Schema.cols.".$parent_data["table"]][$current_data["col"]];
				
			// #pages:controller行
			} elseif ($re == "#pages" && strlen($current_data["controller"])) {
				
				$parent_data =$current_data;
				$ref =& $s["Schema.controller"][$current_data["controller"]];
			
			// #pages:action行
			} elseif ($re == "#pages" && $parent_data["controller"] && strlen($current_data["action"])) {
				
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
						&& ! ($re == "#tables" && in_array($k,array("other","table","col")))
						&& ! ($re == "#pages" && in_array($k,array("other","controller","action")))) { 
					
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
	function get_array_script_node ($arr) {
		
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
	function parse_other ( & $ref, $str) {
		
		foreach (preg_split("!(\r?\n)|\|!",$str) as $sets) {
			
			if (preg_match('!^(.+?)=(.+)$!',$sets,$match))  {
				
				$ref[trim($match[1])] =trim($match[2]);
				
			} elseif (strlen(trim($sets))) {
			
				$ref =trim($sets);
			}
		}
	}
    public function __load_schema ($schema) {
        $this->schema =array();
        
        // Schema.configの処理
        $schema["config"] =$r->apply_filters("config_list_init",(array)$schema["config"]);
        
		// Schema.table/colに対する処理
		foreach ((array)$schema["table"] as $t_name => $t) {
        
            // 参照設定
            $this->schema["table"][$t_name] = & $t;
		    
            // 名前の設定
			$t["name"] =$t_name;
            
            // 前加工
            $t =$this->mod->apply_filter("table_before_init",$t);
            
            // 加工
            $t =$this->mod->apply_filter("table_init",$t);
            
			// Schema.colに関する処理
			foreach ((array)$schema["col"][$t_name] as $tc_name => $tc) {
				
                // 参照設定
                $this->schema["table"][$t_name]["col"][$tc_name] = & $tc;
                
                // ★ nameとfull_nameが逆になっているので注意
                // 名前の設定
                $tc["name"] =$tc_name;
				$tc["full_name"] =$t_name.".".$tc_name;
                $tc["table"] =$t_name;
                
                // tableの名前付参照の設定
				foreach ((array)$tc["ref"] as $ref => $value) {
                    
                    if ($value) {
                        
                        $t["refs"][$ref][] =$tc_name;
                    }
                }
                
                // listの設定
				if ($list_name =$tc["list"]) {
                    
                    // 参照設定
                    $this->schema["list"][$list_name]["name"] =$list_name;
                    $this->schema["list"][$list_name]["col"][$t_name][$tc_name] =$t_name.".".$tc_name;
                }
				
				// 加工
                $tc =$this->mod->apply_filter("col_before_init",$tc);
                $tc =$this->mod->apply_filter("col_init",$tc);
                $tc =$this->mod->apply_filter("col_after_init",$tc);
			}
            
            // 後加工
            $t =$this->mod->apply_filter("table_after_init",$t);
        }
        
        // ★ table_defを構築していないので、SQL生成時に構築すること
        
        // Schema.controllerの処理
		foreach ((array)$schema["controller"] as $c_name => $c) {
			
            // 参照設定
            $this->schema["controller"][$c_name] = & $c;
            
            // 名称の設定
			$c["name"] =$c_name;
            
            // accountの設定
            if ($account_name =$t["account"]) {
                
                // 参照設定
                $this->schema["account"][$account_name]["name"] =$account_name;
                $this->schema["account"][$account_name]["controllers"][$c_name] =$c_name;
            }
            
            // 前加工
            $c =$this->mod->apply_filter("controller_before_init",$c);
            
            // 加工
            $c =$this->mod->apply_filter("controller_init",$c);
            
			// Schema.actionに関する処理
			foreach ((array)$schema["action"][$c_name] as $a_name => $a) {
                
                // 参照設定
                $this->schema["controller"][$c_name]["action"] = & $a;
                
                // 名前の設定
                $a["name"] =$a_name;
				$a["full_name"] =$c_name.".".$a_name;
                $a["controller"] =$c_name;
				
				// 加工
                $a =$this->mod->apply_filter("action_before_init",$a);
                $a =$this->mod->apply_filter("action_init",$a);
                $a =$this->mod->apply_filter("action_after_init",$a);
            }
            
            // 後加工
            $c =$this->mod->apply_filter("controller_after_init",$c);
		}
    }