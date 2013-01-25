<?php

//-------------------------------------
// 
class WebappBuilderDeployFiles extends WebappBuilder {

	protected $tables =array();
	protected $tables_def =array();
		
	//-------------------------------------
	// Schemaからコード生成
	public function deploy_files () {
		
		report("HistoryKey: ".$this->history);
		
		$this->append_history(
				"memo",
				date("Y/m/d H:i"),
				$_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"]);
		
		$this->fetch_table_schema();
		
		// Controllerの構築
		foreach ((array)registry("Schema.controller") as $name => $c) {
			
			$c["name"] =$name;
			$c["wrapper"] =$c["wrapper"] ? $c["wrapper"] : 'default';
			$c["header"] =$c["header"] ? $c["header"]
					: '{{include file="path:/element/'.$c["wrapper"].'_header.html"}}';
			$c["footer"] =$c["footer"] ? $c["footer"]
					: '{{include file="path:/element/'.$c["wrapper"].'_footer.html"}}';
					
			$method_name ="build_controller_".$c["type"];
			
			registry("Schema.controller.".$name,$c);
			
			$this->$method_name($c);
		}
		
		foreach ((array)$this->tables as $t_name => $t) {
		
			foreach ((array)$t["fields"] as $tc_name => $tc) {
				
				if ($tc["list"]) {
	
					// Listの構築
					$src =find_include_path(
							"modules/webapp_skel/list/ProductPriceList.class.php");
					$dest =registry("Path.webapp_dir")
							."/app/list/".str_camelize($tc["list"])."List.class.php";
					$this->arch_template($src,$dest,array("t" =>$t, "tc" =>$tc));
				}
			}
			
			if ( ! $t["nomodel"]) {
				
				// Modelの構築
				$src =find_include_path(
						"modules/webapp_skel/model/ProductModel.class.php");
				$dest =registry("Path.webapp_dir")
						."/app/model/".str_camelize($t["name"])."Model.class.php";
				$this->arch_template($src,$dest,array("t" =>$t));
			}
		}
		
		// configの構築
		foreach (array(
			"routing.config.php",
			"label.config.php",
			"ayth.config.php",
			"install.sql",
		) as $key) {
		
			$src =find_include_path(
					"modules/webapp_skel/config/".$key);
			$dest =registry("Path.webapp_dir")
					."/config/_".$key;
			$this->arch_template($src,$dest,array(
					"s" =>registry("Schema"), 
					"ts"=>$this->tables,
					"td"=>$this->tables_def));
		}
	}
	
	//-------------------------------------
	// 
	protected function build_controller_master ($c) {
		
		// テーブル情報参照
		$t =$this->tables[$c["table"]];
		
		$a_list =array();
		
		if ($c["usage"] != "form") {
		
			$a_list[] =array("name"=>"view_list", "label"=>"一覧");
			$a_list[] =array("name"=>"view_detail", "label"=>"詳細");
		}
		
		if ($c["usage"] != "view") {
		
			$a_list[] =array("name"=>"entry_form", "label"=>"入力");
			$a_list[] =array("name"=>"entry_confirm", "label"=>"入力確認");
			$a_list[] =array("name"=>"entry_exec", "label"=>"入力完了");
		}
		
		// HTMLの構築
		foreach ($a_list as $a) {
		
			$src =find_include_path(
					"modules/webapp_skel/master/product_master.".$a["name"].".html");
			$dest =registry("Path.webapp_dir")
					."/html/".$c["name"]."/".$c["name"].".".$a["name"].".html";
			$this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
		}
		
		// Controllerの構築
		$src =find_include_path(
				"modules/webapp_skel/master/ProductMasterController.class.php");
		$dest =registry("Path.webapp_dir")
				."/app/controller/".str_camelize($c["name"])."Controller.class.php";
		$this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));
	}
	
	//-------------------------------------
	// 
	protected function build_controller_login ($c) {
		
		// HTMLの構築
		foreach (array(
			array("name"=>"entry_form", "label"=>"ログイン"),
		) as $a) {
		
			$src =find_include_path(
					"modules/webapp_skel/login/member_login.".$a["name"].".html");
			$dest =registry("Path.webapp_dir")
					."/html/".$c["name"]."/".$c["name"].".".$a["name"].".html";
			$this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
		}
		
		// Controllerの構築
		$src =find_include_path(
				"modules/webapp_skel/login/MemberLoginController.class.php");
		$dest =registry("Path.webapp_dir")
				."/app/controller/".str_camelize($c["name"])."Controller.class.php";
		$this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));
		
		// Contextの構築
		$src =find_include_path(
				"modules/webapp_skel/login/MemberAuthContext.class.php");
		$dest =registry("Path.webapp_dir")
				."/app/context/".str_camelize($c["account"])."AuthContext.class.php";
		$this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));
	}
	
	//-------------------------------------
	// 
	protected function build_controller_index ($c) {
		
		// HTMLの構築
		foreach (array(
			array("name"=>"index", "label"=>""),
		) as $a) {
		
			$src =find_include_path(
					"modules/webapp_skel/index/product_master.".$a["name"].".html");
			$dest =registry("Path.webapp_dir")
					."/html/".$c["name"]."/".$a["name"].".html";
			$this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
		}
		
		// Controllerの構築
		$src =find_include_path(
				"modules/webapp_skel/index/ProductMasterController.class.php");
		$dest =registry("Path.webapp_dir")
				."/app/controller/".str_camelize($c["name"])."Controller.class.php";
		$this->arch_template($src,$dest,array("c" =>$c, "t" =>$t));
	}
	
	//-------------------------------------
	// 
	protected function fetch_table_schema () {
		
		// テーブルごとに処理
		foreach ((array)registry("Schema.tables") as $t_name => $t) {
		
			$t["name"] =$t_name;
			
			$syskeys =array("pkey","reg_date","del_flg","update_date");
			
			foreach ($syskeys as $key) {
				
				if ($t[$key]) { 
					
					$t[$key] =$t_name.".".$t[$key]; 
					$syskeys[$key] =$t[$key];
				}
			}
			
			// カラムごとに処理
			foreach ((array)registry("Schema.cols.".$t_name) as $tc_name => $tc) {
				
				$tc["name"] =$t_name.".".$tc_name;
				
				// データ表現別のオプション付加
				if ($tc['type'] == "date") {
					
					$tc['modifier'] ='|date:"Y/m/d"';
				}
				
				if ($tc['type'] == "textarea") {
					
					$tc['modifier'] ='|nl2br';
					$tc['input_option'] =' cols="40" rows="5"';
				}
				
				if ($tc['type'] == "text") {
				
					$tc['input_option'] =' size="40"';
				}
				
				if ($tc['type'] == "password") {
					
					$tc['modifier'] ='|hidetext';
					$tc['input_option'] =' size="40"';
				}
				
				if ($tc['type'] == "file") {
					
					$tc['modifier'] ='|userfile:"image"';
					$tc['input_option'] =' group="image"';
				}
				
				if ($tc['type'] == "checkbox") {
					
					$tc['modifier'] ='|selectflg';
					$tc['input_option'] =' value="1"';
				}
				
				if ($tc['type'] == "select" || $tc['type'] == "radioselect") {
					
					$tc['modifier'] ='|select:"'.$tc['list'].'"';
					$tc['input_option'] =' options="'.$tc['list'].'"';
				}
				
				if ($tc['type'] == "checklist") {
					
					$tc['modifier'] ='|select:"'.$tc['list'].'"|@implode:" "';
					$tc['input_option'] =' options="'.$tc['list'].'"';
				}
				
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
			}
			
			$t["fields"] =(array)$t["fields"];
			$t["cols"] =(array)$t["cols"];
			
			$this->tables[$t_name] =$t;
		}
		
		// DB初期化SQL構築
		foreach ((array)$this->tables as $t_name => $t) {
			
			if ($t["virtual"]) {
				
				continue;
			}
			
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
		
		foreach ($fields as $tc_name => $tc) {
		
			if ($type == "search"
					&& ($tc["type"] == "textarea"
					|| $tc["type"] == "file"
					|| $tc["type"] == "password")) {
				
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
}