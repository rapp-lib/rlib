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
		
			foreach ((array)$t["cols"] as $tc_name => $tc) {
				
				if ($tc["list"]) {
	
					// Listの構築
					$src =find_include_path(
							"modules/webapp_skel/list/ProductPriceList.class.php");
					$dest =registry("Path.webapp_dir")
							."/app/list/".str_camelize($tc["list"])."List.class.php";
					$this->arch_template($src,$dest,array("t" =>$t, "tc" =>$tc));
				}
			}
			
			if ( ! $t["virtual"]) {
				
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
		
		// HTMLの構築
		foreach (array(
			array("name"=>"view_list", "label"=>"一覧"),
			array("name"=>"view_detail", "label"=>"詳細"),
			array("name"=>"entry_form", "label"=>"編集"),
			array("name"=>"entry_confirm", "label"=>"編集確認"),
		) as $a) {
		
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
	protected function build_controller_form ($c) {
		
		// テーブル情報参照
		$t =$this->tables[$c["table"]];
		
		// HTMLの構築
		foreach (array(
			array("name"=>"entry_form", "label"=>"入力"),
			array("name"=>"entry_confirm", "label"=>"入力確認"),
			array("name"=>"entry_exec", "label"=>"完了"),
		) as $a) {
		
			$src =find_include_path(
					"modules/webapp_skel/form/product_master.".$a["name"].".html");
			$dest =registry("Path.webapp_dir")
					."/html/".$c["name"]."/".$c["name"].".".$a["name"].".html";
			$this->arch_template($src,$dest,array("c" =>$c, "a" =>$a, "t" =>$t));
		}
		
		// Controllerの構築
		$src =find_include_path(
				"modules/webapp_skel/form/ProductMasterController.class.php");
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
	protected function fetch_table_schema () {
		
		// DB構造構築
		foreach ((array)registry("Schema.tables") as $t_name => $t) {
		
			$t["name"] =$t_name;
			
			$syskeys =array("pkey","reg_date","del_flg","update_date");
			
			foreach ($syskeys as $key) {
				
				if ($t[$key]) { 
					
					$t[$key] =$t_name.".".$t[$key]; 
					$syskeys[$key] =$t[$key];
				}
			}
			
			foreach ((array)registry("Schema.cols.".$t_name) as $tc_name => $tc) {
				
				$tc["name"] =$t_name.".".$tc_name;
				
				if ($tc['list']) {
					
					$tc['modifier'] ='|select:"'.$tc['list'].'"';
					$tc['input_option'] =' options="'.$tc['list'].'"';
					$tc['search_input_option'] =' zerooption="--全て--"';
				}
				
				if ($tc['type'] == "file") {
					
					$tc['modifier'] ='|userfile';
				}
				
				$t["cols"][$tc["name"]] =$tc;
				
				if ( ! in_array($tc_name,$syskeys) 
						&& $tc['type'] != "key"
						&& $tc['type'] != "") {
					
					$t["fields"][$tc["name"]] =$tc;
					
					if ($counter[$tc_name][3]++<=3) {
						
						$t["fields_3"][$tc["name"]] =$tc;
					}
					
					if ($counter[$tc_name][5]++<=3) {
						
						$t["fields_5"][$tc["name"]] =$tc;
					}
				}
			}
			
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
					'',$t["pkey"]);
			
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
			}
		}
			
		report("Fetched table-schema.",array(
			"tables" =>$this->tables,
			"tables_def" =>$this->tables_def,
		));
	}
}