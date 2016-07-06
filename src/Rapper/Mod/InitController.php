<?php

namespace R\Lib\Rapper\Mod;

/**
 * Controllerに関するschema/deployの生成
 */
class InitController extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
		
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
			
			$c =$r->schema("controller.".$a["controller"]);
			$_id =$a["_id"];
			
			$r->deploy("action_method.".$_id,array(
				"data_type" =>"php_tmpl",
				"tmpl_file" =>"controller/".$c["type"]."/".$a["type"].".php",
			));
			
			// HTMLなし
			if ( ! $a["no_html"]) {
				
				$r->deploy("action_html.".$_id,array(
					"data_type" =>"php_tmpl",
					"tmpl_file" =>"html/".$c["type"]."/".$a["type"].".html",
					"dest_file" =>"html/".$a["path"],
				));
			}
		});
		
		// init.deploy [controller]
		// ->app/controller/XxxController.class.php
		//   class XxxController
		//   proc.deploy [action]
		$r->add_filter("init.deploy.controller",array(),function($r, $c) {
			
			$r->deploy("controller_class.".$c["_id"],array(
				"data_type" =>"php_tmpl",
				"tmpl_file" =>"controller/Xxx".str_camelize($c["type"])."Controller.class.php",
				"dest_file" =>"app/controller/".str_camelize($c["_id"])."Controller.class.php",
				"tmpl_schema" =>array(
					"c" =>"controller.".$c["_id"],
				),
			));
		});
		
		// ダミー関数
		$r->register_method("filter_fields",function ($r, $cols, $type) {
		});
		
		// Fieldの絞り込み取得
		$r->register_method("get_fields",function ($r, $c_id, $type) {
			/*
			fields
				search
			c.has.list_setting
			c.has.csv_setting
			*/
		});
		
		// Controllerクラス内でのメソッドのソースを取得
		$r->register_method("get_controller_method",function ($r, $c_id) {
			return array();
		});
	}
}