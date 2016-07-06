<?php

namespace R\Lib\Rapper\Mod;

/**
 * ListOptionに関するschema/deployの生成
 */
class InitListOption extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
		
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
				"tmpl_file" =>"list/XxxList.class.php",
				"dest_file" =>"app/list/".str_camelize($list_option["_id"])."List.class.php",
			));
		});
	}
}