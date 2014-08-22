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
			"app/widget",
		),
		
		"Report.error_reporting" =>E_ALL&~E_NOTICE&~E_DEPRECATED,
		"Report.buffer_enable" =>false,
		"Report.output_to_file" =>null,
	));
	registry(array(
		"Config.error_document" =>array(
			"404" =>registry("Path.html_dir")."/errors/404.html",
			"500" =>registry("Path.html_dir")."/errors/500.html",
		),
	));

	foreach (glob(dirname(__FILE__).'/*.config.php') as $config_file) {
		
		include_once($config_file);
	}
	