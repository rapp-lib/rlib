<?php
	
	ini_set("display_errors",false);
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
		
		// php.ini設定
		"Config.php_ini" =>array_escape(array(
		)),
		
        // cake設定
        "Config.cake_lib" =>"rlib_cake2",
        
		// デバッグ設定
		"Report.error_reporting" =>E_ALL&~E_NOTICE&~E_STRICT&~E_DEPRECATED,
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
	foreach ((array)glob(dirname(__FILE__).'/*.env-ident') as $env_ident_file) {
		
		if (preg_match('!/([^\./]+)\.env-ident$!',$env_ident_file,$match)) {
			
			$env_id =$match[1];
			
			registry(array(
				"Config.env.env_id" =>$env_id,
			));
			
			$env_config =registry("Config.envs.".$env_id.".overwrite_config");
			
			registry(array_escape((array)$env_config));
			
			break;
		}
	}
	
	//-------------------------------------
	// 読み込み元別設定の上書き
	if (getenv("LOADER_ID")) {
		
		$loader_id =getenv("LOADER_ID");
		
		registry(array(
			"Config.loader.loader_id" =>$loader_id,
		));
		
		$loader_config =registry("Config.loaders.".$loader_id.".overwrite_config");
		
		registry(array_escape((array)$loader_config));
	}
	