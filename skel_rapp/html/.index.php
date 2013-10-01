<?php
	
	require_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;
	
	//-------------------------------------
	// start
	function __start () {
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
		$webapp_log =& ref_globals("webapp_log");
		elapse("webapp");
		elapse("webapp.prepare");
		
		// 設定の上書き
		registry("Request.request_uri",$_REQUEST['__REQUEST_URI__']);
		unset($_REQUEST['__REQUEST_URI__']);
		unset($_GET['__REQUEST_URI__']);
		
		// 初期設定の適応
		start_webapp();
		
		// リクエスト情報の解釈
		$request_uri =registry("Request.request_uri");
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		
		$request_file =preg_replace(
				'!/$!','/index.html',$document_root_dir.$request_uri);
		
		$request_path =preg_replace(
				'!^'.preg_quote($html_dir).'!','',$request_file);
		
		list($request_page, $ext_path, $ext_params) 
				=path_to_page($request_path,true);
		
		if ($request_path != $ext_path) {
			
			$request_file =path_to_file($ext_path);
			$request_path =$ext_path;
			array_registry($_REQUEST,$ext_params);
		}
	
		// 対応するpageのroutingがない、静的ページの表示
		if ( ! $request_page) {
			
			// HTMLは表示可能、page=static.index
		 	if (file_exists($request_file)) {
			
				$request_page ="static.index";
			
			// HTMLファイルもない、404エラー
			} else {
			
				report_warning("Request error: Route and File Not found.",array(
					"request_uri" =>$request_uri,
					"request_path" =>$request_path,
					"request_page" =>$request_page,
					"request_file" =>$request_file,
				));
				
				set_response_code(404);
				
				shutdown_webapp("notfound");
			}
		}
		
		// 強制HTTPSアクセス設定
		if ($force_https =registry("Routing.access_only.https")) {
			
			$is_https =$_SERVER["HTTPS"];
			$is_force_https =in_path($request_path,$force_https);
			
			$redirect_url =path_to_url($request_path,true);
			$redirect_url =url($redirect_url,$_GET);
				
			// HTTPSへ転送
			if ($is_force_https && ! $is_https) {
				
				$redirect_url =preg_replace('!^http://!','https://',$redirect_url);
				redirect($redirect_url);
				
			// HTTPへ転送
			} elseif ( ! $is_force_https && $is_https) {
				
				$redirect_url =preg_replace('!^https://!','http://',$redirect_url);
				redirect($redirect_url);
			}
		}
		
		list($controller_name, $action_name) =explode('.',$request_page,2);
		
		$controller_class_name =str_camelize($controller_name)."Controller";
		
		$action_method_name ="act_".$action_name;
		
		registry(array(
			"Request.request_file" =>$request_file,
			"Request.request_path" =>$request_path,
			"Request.request_page" =>$request_page,
			"Request.controller_name" =>$controller_name,
			"Request.action_name" =>$action_name,
			"Request.controller_class_name" =>$controller_class_name,
			"Request.action_method_name" =>$action_method_name,
		));
	
		// レスポンスの設定
		if (preg_match('!Docomo/[12]!',$_SERVER["HTTP_USER_AGENT"])) {
			
			output_rewrite_var(session_name(),session_id());
			registry("Response.content_type", 'application/xhtml+xml');
		
		} else {
			
			$response_charset =registry("Config.external_charset");
			registry("Response.content_type", 'text/html; charset='.$response_charset);
		}
		
		registry("Response.template_file", $request_file);
		
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
		$template_file =registry("Response.template_file");
		$output =$controller_class->fetch($template_file);
		
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
			
			set_response_code(500);
		}
		
		$webapp_log =& ref_globals("webapp_log");
		
		$webapp_log["ShutdownCause"] =$cause;
		$webapp_log["Elapsed"] =elapse();
		
		report("WebappLog",$webapp_log);
	}