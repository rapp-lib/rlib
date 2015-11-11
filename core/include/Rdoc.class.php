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
		
		// 【DEPRECATED】旧webapp_build系統
		if (get_webapp_dync("webapp_build") && $_REQUEST["exec"]) {
			
			add_include_path(dirname(__FILE__)."/Rdoc");
			
			$options =registry("Config.dync.webapp_build");
			
			if ($options["rollback"]) {
				
				$obj =obj("WebappBuilderRollbackFiles");
				$obj->init($options);
				$obj->rollback_files();
			
			} elseif ($options["schema"]) {
				
				$obj =obj("WebappBuilderCreateSchema");
				$obj->init($options);
				$obj->create_schema();
				
			} elseif ($options["deploy"]) {
			
				$obj =obj("WebappBuilderDeployFiles");
				$obj->init($options);
				$obj->deploy_files();
				
			} elseif ($options["profile"]) {
				
				$obj =obj("WebappBuilderScriptScanner");
				$obj->init($options);
				$obj->profile_system();
				
			} elseif ($options["readme"]) {
				
				$obj =obj("WebappBuilderReadme");
				$obj->init($options);
				$obj->echo_readme();
				
			} elseif ($options["datastate"]) {
				
				$obj =obj("WebappBuilderDataState");
				$obj->init($options);
				$obj->fetch_datastate();
			}
			
			shutdown_webapp("rdoc");
		}
	}
}