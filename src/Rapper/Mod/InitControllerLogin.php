<?php

namespace R\Lib\Rapper\Mod;

/**
 * login系ControllerのSchema/Deploy生成
 */
class InitControllerLogin extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
	
		// schema.actionの補完
		$r->add_filter("init.schema.controller",array("cond"=>array("type"=>"login")),function($r,$c) {
			
			$r->schema("action.".$c["_id"].".entry_form", array(
				"action" =>"entry_form",
				"_id" =>$c["_id"].".entry_form",
				"label" =>"ログイン",
				"type" =>"login.entry_form",
				"controller" =>$c["_id"],
			));
			$r->schema("action.".$c["_id"].".entry_confirm", array(
				"action" =>"entry_confirm",
				"_id" =>$c["_id"].".entry_confirm",
				"label" =>"ログイン確認",
				"type" =>"login.entry_confirm",
				"no_html" =>1,
				"controller" =>$c["_id"],
			));
			$r->schema("action.".$c["_id"].".logout",array(
				"action" =>"logout",
				"_id" =>$c["_id"].".logout",
				"label" =>"ログアウト",
				"type" =>"login.logout",
				"no_html" =>1,
				"controller" =>$c["_id"],
			));
		});
	}
}