<?php
	
	require_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;
	
	//-------------------------------------
	// start
	function __start () {
		
		// リクエストURLの取得
		registry("Request.request_uri",$_REQUEST['__REQUEST_URI__']);
		unset($_REQUEST['__REQUEST_URI__']);
		unset($_GET['__REQUEST_URI__']);
		
		elapse("webapp");
		elapse("webapp.setup");
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
		// 初期設定の適応
		start_webapp();
		
		//-------------------------------------
		// リクエスト情報の解決
		$request_uri =registry("Request.request_uri");
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		
		$request_file =preg_replace('!/$!','/index.html',$document_root_dir.$request_uri);
		$request_path =preg_replace('!^'.preg_quote($html_dir).'!','',$request_file);
		list($request_page, $ext_path, $ext_params) =path_to_page($request_path,true);
		
		// 静的ページのStaticControllerへの対応付け
		if ( ! $request_page && file_exists($request_file)) {
			
			$request_page ="static.index";
		}
				
		// 動的パス埋め込みパラメータの解決
		if ($request_path != $ext_path) {
			
			$request_file =path_to_file($ext_path);
			$request_path =$ext_path;
			
			array_registry($_REQUEST,$ext_params);
		}
		
		registry(array(
			"Request.request_file" =>$request_file,
			"Request.request_path" =>$request_path,
			"Request.request_page" =>$request_page,
		));
		
		// Routing設定もなくHTMLファイルもない場合は404エラー
		if ( ! $request_page && ! file_exists($request_file)) {
			
			report_warning("RequestError: Route and File NotFound",registry("Request"));
			
			set_response_code(404);
			
			shutdown_webapp("notfound");
		}
	
		// レスポンスの設定
		$request_file =registry("Request.request_file");
		registry("Response.template_file", $request_file);
		
		$response_charset =registry("Config.external_charset");
		registry("Response.content_type", 'text/html; charset='.$response_charset);
		
		//-------------------------------------
		// ControllerとActionの解決
		$request_page =registry("Request.request_page");
		list($controller_name, $action_name) =explode('.',$request_page,2);
		$controller_class_name =str_camelize($controller_name)."Controller";
		$action_method_name ="act_".$action_name;
		
		registry(array(
			"Request.controller_name" =>$controller_name,
			"Request.action_name" =>$action_name,
			"Request.controller_class_name" =>$controller_class_name,
			"Request.action_method_name" =>$action_method_name,
		));
		
		// Controllerクラスが存在しないエラー
		if ( ! class_exists($controller_class_name)) {
			
			report_error("RoutingError: Controller NotFound",registry("Request"));
		}
		
		elapse("webapp.setup",true);
		elapse("webapp.action");
		
		//-------------------------------------
		// Controllerのセットアップ、actionの実行
		$controller_obj =new $controller_class_name($controller_name,$action_name);
		
		$controller_obj->before_act();
		
		if (is_callable(array($controller_obj,$action_method_name))) {
			
			// Controller::act_*()の呼び出し
			$controller_obj->$action_method_name();
		}
		
		$controller_obj->after_act();
		
		elapse("webapp.action",true);
		elapse("webapp.fetch");
		
		registry("Response.template_vars", $controller_obj->_tpl_vars);
		
		//-------------------------------------
		// テンプレートファイルの読み込み
		$template_file =registry("Response.template_file");
		$output =$controller_obj->fetch($template_file);
		
		// 出力
		$content_type =registry("Response.content_type");
		header("Content-type: ".$content_type);
		print($output);
		
		elapse("webapp.fetch",true);
		elapse("webapp",true);
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
		
		if ($cause == "error_report") {
			
			// 異常停止のログを記録
			$log_file =registry("Path.tmp_dir")."/log/error_shutdown.log";
			$msg ="-- ERROR ".date("Y/m/d H:i:s")." --\n".print_r($options,true)."\n\n";
			file_put_contents($log_file,$msg,FILE_APPEND|LOCK_EX);

			set_response_code(500);
		}
		
		report("WebappLog",array(
			"RequestPath" =>registry("Request.request_path"),
			"RequestPage" =>registry("Request.request_page"),
			"Template" =>registry("Response.template_file"),
			"ShutdownCause" =>$cause,
			"Elapsed" =>elapse(),
			"TemplateVars" =>registry("Response.template_vars"),
		));
	}
