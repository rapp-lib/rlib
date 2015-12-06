<?php

//-------------------------------------
// 
class Rdoc {

	//-------------------------------------
	// 起動パラメータの確認
	public function check () {
	
		if (get_webapp_dync("report") && $_POST["__rdoc"]) {
			
			add_include_path(dirname(__FILE__)."/Rdoc");
			
			$params =$_POST["__rdoc"];
			$entry =$params["entry"];
			$module =load_module("rdoc_entry",$entry);
			
			if ( ! is_callable($module)) {
				
				report_error("Rdoc load failur",array(
					"entry" =>$entry,
					"module" =>$module,
				));
			}
			
			report("Rdoc load successfuly",array(
				"entry" =>$entry,
				"module" =>$module,
				"params" =>$params,
			));
			
			call_user_func($module,$params);
			
			shutdown_webapp("rdoc");
		}
	}
}