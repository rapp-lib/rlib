<?php

	ini_set("display_errors",true);
	error_reporting(E_ALL&~E_NOTICE);
	
	require_once(dirname(__FILE__).'/../../../core.php');
	include_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;
	
	//-------------------------------------
	// start
	function __start () {
		
		$webapp_log =& ref_globals("webapp_log");
		elapse("webapp");
		elapse("webapp.prepare");
		
		// 設定の上書き
		$_SERVER["DOCUMENT_ROOT"] =realpath(str_replace(
				$_SERVER['SCRIPT_NAME'],"",$_SERVER['SCRIPT_FILENAME']));
		
		$_SERVER["REQUEST_URI"] =$_REQUEST['__REQUEST_URI__'];
		unset($_REQUEST['__REQUEST_URI__']);
		unset($_GET['__REQUEST_URI__']);
		
		// 初期設定の適応
		start_webapp();
		
		// システム再構築機能
		if (get_webapp_dync("webapp_build") && $_REQUEST["exec"]) {
			
			obj("WebappBuilder")->webapp_build(registry("Config.dync.webapp_build"));
			exit;
		}
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
		// リクエスト情報の解釈
		$request_uri =$_SERVER["REQUEST_URI"];
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		
		$request_file =preg_replace(
				'!/$!','/index.html',$document_root_dir.$request_uri);
		
		$request_path =preg_replace(
				'!^'.preg_quote($html_dir).'!','',$request_file);
		
		$request_page =path_to_page($request_path);
		
		list($controller_name, $action_name) =explode('.',$request_page,2);
		
		$template_file =$request_file;
		
		$controller_class_name =str_camelize($controller_name)."Controller";
		
		$action_method_name ="act_".$action_name;
		
		registry(array(
			"Request.request_uri" =>$request_uri,
			"Request.request_file" =>$request_file,
			"Request.request_path" =>$request_path,
			"Request.request_page" =>$request_page,
			"Request.template_file" =>$template_file,
			"Request.controller_name" =>$controller_name,
			"Request.action_name" =>$action_name,
			"Request.controller_class_name" =>$controller_class_name,
			"Request.action_method_name" =>$action_method_name,
		));
	
		// 404エラー
		if ( ! $request_page) {
			
			report_error("Request error: Route Not found.",array(
				"request_uri" =>$request_uri,
				"request_path" =>$request_path,
			));
		}
		
		// Controllerクラスが存在しないエラー
		if ( ! class_exists($controller_class_name)) {
			
			report_error("Routing error: Controller Not found.",array(
				"request_path" =>$request_path,
				"controller_class_name" =>$controller_class_name,
			));
		}
		
		// Controllerインスタンス作成
		$controller_class =new $controller_class_name($controller_name,$action_name);
		
		$webapp_log["RequestPath"] =$request_path;
		$webapp_log["Controller"] =$controller_class;
		$webapp_log["Action"] =$action_name;
		$webapp_log["Template"] =$template_file;
		
		elapse("webapp.prepare",true);
		elapse("webapp.action");
		
		// Controller::before_act()の呼び出し
		$controller_class->before_act();
		
		// Controller::act_*()の呼び出し
		if (is_callable(array($controller_class,$action_method_name))) {
		
			$controller_class->$action_method_name();
		}
		
		// Controller::after_act()の呼び出し
		$controller_class->after_act();
		
		elapse("webapp.action",true);
		elapse("webapp.fetch");
		
		// テンプレートファイルの読み込み
		$output =$controller_class->fetch($template_file);
		
		$content_type =strstr($_SERVER["HTTP_USER_AGENT"],'DoCoMo/2')
				? 'application/xhtml+xml'
				: 'text/html';
		
		// 出力
		header("Content-type: ".$content_type);
		print($output);
		
		elapse("webapp.fetch",true);
		elapse("webapp",true);
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
		
		$webapp_log =& ref_globals("webapp_log");
		
		$webapp_log["ShutdownCause"] =$cause;
		$webapp_log["Elapsed"] =elapse();
		
		report("WebappLog",$webapp_log);
	}