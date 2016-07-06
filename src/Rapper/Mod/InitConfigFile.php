<?php

namespace R\Lib\Rapper\Mod;

/**
 * コンフィグファイルに関するschema/deployの生成
 */
class InitConfigFile extends BaseMod {

	/**
	 * 
	 */ 
	public function install () {
		
		$r =$this->r;
		
		// init.schema ["routing","label","auth","install_sql"]
		// ->[config_file]
		$r->add_filter("init.schema",array(),function($r, $s) {
			
			$s["config_file"]["routing"] =array("_id" =>"routing_config", "file" =>"routing.config.php");
			$s["config_file"]["label"] =array("_id" =>"label_config", "file" =>"label.config.php");
			$s["config_file"]["auth"] =array("_id" =>"auth_config", "file" =>"auth.config.php");
			$s["config_file"]["install_sql"] =array("_id" =>"install_sql", "file" =>"install.sql");
			
			return $s;
		});
		
		// init.deploy [config_file]
		// ->{id=config_file.xxx ,dest_file=/config/xxx.config.php, data_type=php_tmpl}
		$r->add_filter("init.deploy.config_file",array(),function($r, $config_file) {
			
			$r->deploy("config_file.".$config_file["_id"],array(
				"data_type" =>"php_tmpl",
				"tmpl_file" =>"config_file/".$config_file["file"],
				"dest_file" =>"app/config/".$config_file["file"],
			));
		});
	}
}