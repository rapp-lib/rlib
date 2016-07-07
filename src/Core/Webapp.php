<?php
/*
	2016/07/07
		registry -> Vars::registry
		sanitize -> String::sanitizeRequest
 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
use R\Lib\Core\String;
/**
 * 
 */
class Webapp {

	/**
	 * [start_webapp description] 
	 */
	public static function startWebapp () {
		
		// Registryのデフォルト値の補完
		$registry_defaultset =array(
			
			// パス設定
			"Path.lib_dir" =>RLIB_ROOT_DIR,
			"Path.tmp_dir" =>"/tmp",
			"Path.document_root_dir" =>realpath($_SERVER["DOCUMENT_ROOT"]),
			"Path.webapp_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])."/.."),
			"Path.html_dir" =>realpath(dirname($_SERVER['SCRIPT_FILENAME'])),
			
			// エンコーディング設定
			"Config.internal_charset" =>"UTF-8",
			"Config.external_charset" =>"SJIS-WIN",
			
			// Dync機能設定
			"Config.dync_key" =>null,
			
			// セッション設定
			"Config.session_lifetime" =>86400,
			"Config.session_start_function" =>"std_session_start",
			
			// webapp_dir内のinclude_path設定
			"Config.webapp_include_path" =>array(
				"app",
				"app/include",
				"app/controller",
				"app/context",
				"app/list",
				"app/model",
				"app/widget",
			),
			
			// ライブラリ読み込み設定
			"Config.load_lib" =>array(
				"lib_context",
				"lib_db",
				"lib_smarty",
			),
			
			// レポート出力設定
			"Report.error_reporting" =>E_ALL&~E_NOTICE&~E_STRICT&~E_DEPRECATED,
		);
		
		foreach ($registry_defaultset as $k => $v) {
			
			if (Vars::registry($k) === null) {
				
				Vars::registry($k,$v);
			}
		}
		
		// php.ini設定
		foreach ((array)Vars::registry("Config.php_ini") as $k => $v) {
			
			ini_set($k, $v);
		}

		// HTTPパラメータ構築
		$_REQUEST =array_merge($_GET,$_POST);
		
		// 入出力文字コード変換
		ob_start("mb_output_handler_impl");
		mb_convert_variables(
				Vars::registry("Config.internal_charset"),
				Vars::registry("Config.external_charset"),
				$_REQUEST);
		$_REQUEST =String::sanitizeRequest($_REQUEST);
		
		// PHPの設定書き換え
		spl_autoload_register("load_class");
		set_error_handler("std_error_handler",E_ALL);
		set_exception_handler('std_exception_handler');
		register_shutdown_function('std_shutdown_handler');
		
		if ( ! get_cli_mode()) {
		
			// session_start
			call_user_func(Vars::registry("Config.session_start_function"));
			
			// Dync機能の有効化
			start_dync();
		}
		
		// include_pathの設定
		foreach ((array)Vars::registry("Config.webapp_include_path") as $k => $v) {
			
			add_include_path(Vars::registry("Path.webapp_dir")."/".$v);
		}

		// ライブラリの読み込み
		foreach ((array)Vars::registry("Config.load_lib") as $k => $v) {
			
			load_lib($v);
		}
		
		obj("Rdoc")->check();
	}

	//-------------------------------------
	// std_session_start
	/**
	 * [std_session_start description] 
	 */
	public static function stdSessionStart () {
		
		// セッションの開始
		$session_lifetime =Vars::registry("Config.session_lifetime");
		ini_set("session.gc_maxlifetime",$session_lifetime);
		ini_set("session.cookie_lifetime",$session_lifetime);
		ini_set("session.cookie_httponly",true);
		ini_set("session.cookie_secure",$_SERVER['HTTPS']);
		
		// Probrem on IE and https filedownload
		// http://www.php.net/manual/en/function.session-cache-limiter.php#48822
		session_cache_limiter('');
		header("Pragma: public");
		header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
		
		header("P3P: CP='UNI CUR OUR'");
		
		session_start();
	}

}
