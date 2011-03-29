<?php

	registry(array(
	
		// 基本設定
		"Path.tmp_dir" =>realpath(dirname(__FILE__)."/../tmp"),
		"Config.dync_key" =>"_",
		"Config.dync_auth_id" =>"e77989ed21758e78331b20e477fc5582",
		"Config.dync_auth_pw" =>"547d913f6ee96d283eb4d50aea20acc1",
		"Config.dtrack_key" =>"__dtrack",
		"Config.external_charset" =>"SJIS-WIN",
		"Config.dtrack_bind_session" =>false,
		"Report.error_reporting" =>E_ALL&~E_NOTICE,
		"Config.webapp_include_path" =>array(
			"app",
			"app/controller",
			"app/context",
			"app/list",
			"app/provider",
		),
		"Config.load_lib" =>array(
			"lib_smarty",
			"lib_context",
			"lib_db",
		),
		
		// DB接続
		"DBI.preconnect" =>array(
			"default" =>array(
				'driver' => 'mysql',
				'persistent' => false,
				'host' => 'localhost',
				'login' => 'dev',
				'password' => 'pass',
				'database' => 'r3_test',
				'prefix' => '',
			),
		),
		
		// ファイルアップロード
		"UserFileManager.upload_dir" =>array(
			"default" =>realpath(dirname(__FILE__)."/../html/save/user_uploaded")."/",
			"group" =>array(
			),
		),
		"UserFileManager.allow_ext" =>array(
			"default" =>null,
			"group" =>array(
			),
		),
		
		// ルーティング
		"Routing.page_to_path" =>array(
			"index.index" =>"/index.html",
		),
		
		// 認証
		// "Auth.access_only.member" =>array(
		// 	"product_master",
		// ),
		
		// ラベル
		// "Label.schema.col.Product.name" =>"製品名",
		// "Label.errmsg.input.required.Product.name" =>"製品名が空白です",
		// "Label.errmsg.user.member_login_failed" =>"IDまたはPassが誤っています",
		
	));

	foreach (glob(dirname(__FILE__).'/*.config.php') as $config_file) {
		
		include_once($config_file);
	}
	