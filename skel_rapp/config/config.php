<?php
	
	ini_set("display_errors",true);
	error_reporting(E_ALL&~E_NOTICE);
	
	require_once(dirname(__FILE__).'/../../rlib.git/core.php');
	
	//-------------------------------------
	// システム動作設定
	registry(array(
	
		// パス設定
		"Path.webapp_dir" =>realpath(dirname(__FILE__)."/.."),
		"Path.tmp_dir" =>realpath(dirname(__FILE__)."/../tmp"),
		"Path.html_dir" =>realpath(dirname(__FILE__)."/../html"),
		"Path.document_root_dir" =>realpath(dirname(__FILE__)."/../html"),
		"Path.document_root_url" =>"",
		
		// 基本設定
		"Config.external_charset" =>"UTF-8",
		"Config.webapp_include_path" =>array(
			"app",
			"app/include",
			"app/controller",
			"app/context",
			"app/list",
			"app/model",
		),
		"Config.dync_key" =>"_",
		"Config.auto_deploy" =>false,
		
		"Report.error_reporting" =>E_ALL&~E_NOTICE&~E_DEPRECATED,
		"Report.buffer_enable" =>false,
		"Report.output_to_file" =>null,
	));

	foreach (glob(dirname(__FILE__).'/*.config.php') as $config_file) {
		
		include_once($config_file);
	}
	