<?php
	
	ini_set("display_errors",true);
	error_reporting(E_ALL&~E_NOTICE);
	
	require_once(dirname(__FILE__).'/../../rlib.git/core.php');
	
	//-------------------------------------
	// システム動作基本設定
	registry("Path.webapp_dir", realpath(dirname(__FILE__)."/.."));
	registry(array(
	
		// パス設定
		"Path.tmp_dir" =>registry("Path.webapp_dir")."/tmp",
		"Path.html_dir" =>registry("Path.webapp_dir")."/html",
		"Path.document_root_dir" =>registry("Path.webapp_dir")."/html",
		"Path.document_root_url" =>"",
		
		// 基本設定
		"Config.dync_key" =>"_",
		"Config.auto_deploy" =>false,
		"Config.external_charset" =>"UTF-8",
		"Config.session_lifetime" =>86400,
		"Config.webapp_include_path" =>array(
			"app",
			"app/include",
			"app/controller",
			"app/context",
			"app/list",
			"app/model",
		),
		
		// デバッグ設定
		"Report.error_reporting" =>E_ALL&~E_NOTICE&~E_DEPRECATED,
		"Report.buffer_enable" =>false,
		"Report.output_to_file" =>null,
		"Report.report_about_dync" =>false,
		"Report.report_backtraces" =>false,
	));
	
	registry(array(
		
		// エラー時転送先設定
		"Config.error_document" =>array(
			"404" =>registry("Path.html_dir")."/errors/404.html",
			"500" =>registry("Path.html_dir")."/errors/500.html",
		),
		
		// 複数サイト対応設定
		"Config.vhosts" =>array(
		),
	));

	//-------------------------------------
	// 各種設定読み込み
	foreach (glob(dirname(__FILE__).'/*.config.php') as $config_file) {
		
		include_once($config_file);
	}
	
	//-------------------------------------
	// 環境別設定の上書き
	foreach (glob(dirname(__FILE__).'/*.env-ident') as $env_ident_file) {
		
		if (preg_match('!/([^\./]+)\.env-ident$!',$env_ident_file,$match)) {
			
			$env_id =$match[1];
			$config_files =glob(dirname(__FILE__).'/*.'.$env_id.'.env-config.php');
			
			foreach ($config_files as $config_file) {
				
				include_once($config_file);
			}
			
			break;
		}
	}
	
	//-------------------------------------
	// ドメイン別設定の上書き
	foreach (registry("Config.vhosts") as $site_id => $site_config) {
		
		$server_names = ! is_array($site_config["server_name"]) 
				? array($site_config["server_name"])
				: $site_config["server_name"];
		$server_name =$server_names[0];
		
		if (in_array($_SERVER["SERVER_NAME"],$server_names)) {
			
			registry(array(
				"Path.document_root_url" =>"http://".$server_name,
				"Path.document_root_url_https" =>"https://".$server_name,
			));
			registry((array)$site_config["overwrite_config"]);
			
			$config_files =glob(dirname(__FILE__).'/*.'.$site_id.'.site-config.php');
			
			foreach ($config_files as $config_file) {
				
				include_once($config_file);
			}
			
			break;
		}
	}
	