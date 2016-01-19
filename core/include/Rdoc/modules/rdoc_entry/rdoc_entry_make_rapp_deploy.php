<?php 
	
	/**
     * schema.config.php→生成/展開
     */ 
	function rdoc_entry_rapper_deploy ($options=array()) {
        
        $work_dir =registry("Path.tmp_dir")."/rapper/"."U".date("ymd-His-").sprintf("%03d",rand(001,999));
        
        $mod =new Rdoc_Mod;
		$rapper =new Rdoc_Rapper($mod);
        
        // 設定
        $mod->config(array(
            "webapp_dir" =>registry("Path.webapp_dir"),
            "deploy_dir" =>registry("Config.auto_deploy")
                    ? registry("Path.webapp_dir")
                    : $work_dir."/deploy",
            "work_dir" =>registry("Path.webapp_dir"),
        ));
        
        // table初期化設定
        $mod->add_filter("table_init",array(), function ($mod, $t) {
            if ( ! $t["def"]["table"]) { $t["def"]["table"] =$t["name"]; }
            return $t;
		});
        
        // col初期化設定
        $mod->add_filter("col_init",array("type"=>"date"), function ($mod, $tc) {
            $tc['modifier'] ='|date:"Y/m/d"';
            $tc['input_option'] =' range="'.date("Y").'~+5" format="{%l}{%yp}{%mp}{%dp}{%datefix}"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"textarea"),function ($mod, $tc) {
            $tc['modifier'] ='|nl2br';
            $tc['input_option'] =' cols="40" rows="5"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"text"),function ($mod, $tc) {
            $tc['input_option'] =' size="40"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"password"),function ($mod, $tc) {
            $tc['modifier'] ='|hidetext';
            $tc['input_option'] =' size="40"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"file"),function ($mod, $tc) {
            $group =$tc['group'] ? $tc['group'] : "public";
            $tc['modifier'] ='|userfile:"'.$group.'"';
            $tc['input_option'] =' group="'.$group.'"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"checkbox"),function ($mod, $tc) {
            $tc['modifier'] ='|selectflg';
            $tc['input_option'] =' value="1"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"select"),function ($mod, $tc) {
            $tc['modifier'] ='|select:"'.$tc['list'].'"';
            $tc['input_option'] =' options="'.$tc['list'].'"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"radioselect"),function ($mod, $tc) {
            $tc['modifier'] ='|select:"'.$tc['list'].'"';
            $tc['input_option'] =' options="'.$tc['list'].'"';
        	return $tc;
		});
        $mod->add_filter("col_init",array("type"=>"checklist"),function ($mod, $tc) {
            $tc['modifier'] ='|select:"'.$tc['list'].'"|@tostring:" "';
            $tc['input_option'] =' options="'.$tc['list'].'"';
        	return $tc;
		});
        $mod->add_filter("col_init",array(),function ($mod, $tc) {
            $tc['input_option'] .=' class="input-'.$tc['type'].'"';
            
            // DB上の定義用の参照設定
            if ($tc['def']['type']) {
                $tc["ref"]["_def"] =1;
            }
            // 入力/表示用の参照設定
            if ($tc['type']) {
                $tc["ref"]["_input"] =1;
                $tc["ref"]["_show"] =1;
            }
            
        	return $tc;
		});
        
        // controller初期化設定
        $mod->add_filter("controller_init",array(),function ($mod, $c) {
			if ($c["wrapper"]) {
                $c["element"]["header"] ="html/element/".$c["wrapper"].'_header.html';
			    $c["element"]["footer"] ="html/element/".$c["wrapper"].'_footer.html';
            }
            return $c;
        });
        $mod->add_filter("controller_init",array("type"=>"master"),function ($mod, $c) {
			if ( ! $c["action"]) {
                $c["action"]["index"] =array(
                    "type" =>"redirect",
                    "redirect_to" =>array("page" =>".view_list"),
                );
                $c["action"]["list"] =array(
                    "label" =>"一覧",
                    "type" =>"view_list",
                    "menu_links" =>array(
                        array("page"=>".edit","label"=>"新規登録"),
                        array("page"=>".csv_export"),
                        array("page"=>".csv_import"),
                    ),
                    "item_links" =>array(
                        array("page"=>".edit","label"=>"編集"),
                        array("page"=>".delete"),
                    ),
                );
                $c["action"]["edit"] =array(
                    "label" =>"編集",
                    "type" =>"edit",
                    "redirect_to" =>array("page" =>".view_list"),
                );
                $c["action"]["delete"] =array(
                    "label" =>"削除",
                    "type" =>"delete",
                    "redirect_to" =>array("page" =>".view_list"),
                );
                $c["action"]["csv_import"] =array(
                    "label" =>"CSVインポート",
                    "type" =>"csv_import",
                    "redirect_to" =>array("page" =>".view_list"),
                );
                $c["action"]["csv_export"] =array(
                    "label" =>"CSVエクスポート",
                    "type" =>"csv_export",
                    "redirect_to" =>array("page" =>".view_list"),
                );
            }
            return $c;
        });
        $mod->add_filter("controller_init",array("type"=>"login"),function ($mod, $c) {
			if ( ! $c["account"]) {
                report_error("controller(.type=login)は.account=の設定が必須",array(
                    "controller" =>$c["name"],
                ));
            }
            if ( ! $c["action"]) {
                $c["action"]["index"] =array(
                    "type" =>"redirect",
                    "redirect_to" =>array("page" =>".login_form"),
                );
                $c["action"]["login"] =array(
                    "label" =>"ログイン",
                    "type" =>"login",
                    "redirect_to" =>array("page" =>"index"),
                );
                $c["action"]["logout"] =array(
                    "label" =>"ログアウト",
                    "type" =>"logout",
                    "redirect_to" =>array("page" =>"index"),
                );
            }
            return $c;
        });
        
        // controller展開設定
        $mod->add_filter("controller_deploy",array(),function ($mod, $c) {
            
            // element.header/footerコピー
            if ($c["element"]["header"]) {
                $src =$rapper->fetch_template("html/element/default_header.html", array("c"=>$c));
                $rapper->deploy_file($c["element"]["header"], $src);
            }
            if ($c["element"]["footer"]) {
                $src =$rapper->fetch_template("element/default_footer.html", array("c"=>$c));
                $rapper->deploy_file($c["element"]["footer"], $src);
            }
        });
        $mod->add_filter("action_deploy",array("type"=>"redirect"),function ($mod, $a) {
        });
        
        $rapper->load_schema(registry("Schema"));
        $rapper->deploy_all();
	}

/**
 * 自動生成エンジン
 */
class Rdoc_Rapper {
    
    protected $mod;
    protected $schema;
    
    /**
     * 初期化
     */
    public function __construct ($mod=null) {
        
        $this->mod =$mod ? $mod : new Rdoc_Mod;
    }
    
    /**
     * 展開の実行
     */
    public function deploy_all () {
        
		foreach ((array)$this->schema["pages"] as $c) {
    		
            // 展開処理
            $c =$this->mod->apply_filter("controller_deploy",$c);
        }
    }
    
    /**
     * Schema構成を読み込む
     */
    public function load_schema ($schema) {
        
        $this->schema =array();
        
		// Schema.tables/colsに対する処理
		foreach ((array)$schema["tables"] as $t_name => $t) {
        
            // 参照設定
            $this->schema["tables"][$t_name] = & $t;
		    
            // 名前の設定
			$t["name"] =$t_name;
            
            // 前加工
            $t =$this->mod->apply_filter("table_before_init",$t);
            
            // 加工
            $t =$this->mod->apply_filter("table_init",$t);
            
			// Schema.colsに関する処理
			foreach ((array)$schema["cols"][$t_name] as $tc_name => $tc) {
				
                // 参照設定
                $this->schema["tables"][$t_name]["cols"][$tc_name] = & $tc;
                
                // 名前付参照の設定
				foreach ((array)$tc["ref"] as $ref => $value) {
                    
                    if ($value) {
                        
                        $t["refs"][$ref][] =$tc_name;
                    }
                }
                
                // ★ nameとfull_nameが逆になっているので注意
                
                // 名前の設定
                $tc["name"] =$tc_name;
				$tc["full_name"] =$t_name.".".$tc_name;
                $tc["table"] =$t_name;
				
				// 加工
                $tc =$this->mod->apply_filter("col_before_init",$tc);
                $tc =$this->mod->apply_filter("col_init",$tc);
                $tc =$this->mod->apply_filter("col_after_init",$tc);
			}
            
            // 後加工
            $t =$this->mod->apply_filter("table_after_init",$t);
        }
        
        // ★ tables_defを構築していないので、SQL生成時に構築すること
        
        // Schema.controllerの処理
		foreach ((array)$schema["controller"] as $c_name => $c) {
			
            // 参照設定
            $this->schema["controller"][$c_name] = & $c;
            
            // 名称の設定
			$c["name"] =$c_name;
            
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
        
    /**
     * table内のcolsを用途に応じて取得
     */
    public function get_fields ($t_name, $ref_tmpl, $ref_page=null) {
        
        $tc_names =array();
        
        // page側での限定があれば優先、なければtmpl中での指定に従う
        if ($ref_page) {
            
            if ( ! $this->schema["tables"][$t_name]["refs"][$ref_page]) {
                
                report_error("Schema.table ref参照解決エラー",array(
                    "ref_page" =>$ref_page,
                    "table" =>$t_name,
                    "refs" =>$this->schema["tables"][$t_name]["refs"],
                ));
            }
             
            $tc_names =$this->schema["tables"][$t_name]["refs"][$ref_page];
            
        } else {
            
            $tc_names =(array)$this->schema["tables"][$t_name]["refs"][$ref_tmpl];
        }
        
        // refsに設定されたtc_nameに対応するfieldを返す
        $fields =array();
        
        foreach ($tc_names as $tc_name) {
            
            $fields[] =$this->schema["tables"][$t_name]["cols"][$tc_name];
        }
        
        return $fields;
    }
		
	/**
     * テンプレートファイルの検索
     */ 
	public function fetch_template ($tmpl_path, $vars=array(), $options=array()) {
		
        // テンプレートファイルの検索
        $tmpl_file =null;
        
        if ($found =find_include_path("modules/rdoc_rapper_tmpl/".$options["tmplset"]."/".$tmpl_path)) {
            
            $tmpl_file =$found;
            
        } elseif ($found =find_include_path("modules/rdoc_rapper_tmpl/".$tmpl_path)) {
            
            $tmpl_file =$found;
            
        } else {
            
            report_error("rdoc_rapper_tmpl検索エラー",array(
                "tmpl_path" =>$tmpl_path,
                "options" =>$options,
            ));
        }
		
        // 値のアサイン
		$rapper =$this;
		$schema =$this->schema;
		$mod =$this->mod;
        extract($vars,EXTR_REFS);
        
        // テンプレートの読み込み
		ob_start();
		include($tmpl_file);
		$src =ob_get_clean();
		$src =str_replace('<!?','<?',$src);
        
		return $src;
	}
	
	/**
     * ファイルの書き込み
     */
	public function deploy_file ($dest_path, $src) {
		
        $webapp_file =$this->mod->config("webapp_dir")."/".$dest_path;
        $dest_file =$this->mod->config("deploy_dir")."/".$dest_path;
        
		// 同一性チェック
		if (file_exists($webapp_file)
			    && crc32(file_get_contents($webapp_file)) == crc32($src)) {
			
			report("Deploy中止:差分なし",array(
				"file" =>$webapp_file,
			));
			
			return true;
		}
		
        // 親dirの作成
		if ( ! file_exists(dirname($filename))) {
			
			$old_umask =umask(0);
			mkdir(dirname($filename),0775,true);
			umask($old_umask);
		}
        
		// ファイルの書き込み
		if (touch($dest_file) && chmod($dest_file,0664)
				&& is_writable($dest_file)
				&& file_put_contents($dest_file,$src)) {
			
			report("Deploy完了",array(
				"file" =>$dest_file,
			));
			print "<code>".sanitize($src)."</code>";
		
		} else {
			
			report_warning("Deploy失敗",array(
				"file" =>$dest_file,
			));
            
			return false;
		}
		
		return true;
	}
}

/**
 * 設定
 */
class Rdoc_Mod {

    protected $config =array();
    protected $filters =array();
    
    /**
     * 
     */
    public function config ($name=null, $value=null) {
        
        return array_registry($this->config,$name,$value);
    }
    
    /**
     * 
     */
    public function add_filter ($type, $conditions, $func) {
        
        $this->filters[$type][] =array(
            "conditions" =>$conditions, 
            "func" =>$func,
        );
    }
    
    /**
     * 
     */
    public function apply_filters ($type, $data) {
        
        foreach ($this->filters[$type] as $filter) {
            
            // 適用条件判定
            foreach ((array)$filter["conditions"] as $k => $v) {
                
                if ($data[$k] != $v) {
                    
                    continue;
                }
            }
            
            // 適用
            $data =$filter["func"]($this, $data);
        }
        
        return $data;
    }
}    
