<?php
	
	require_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;

	//-------------------------------------
	// start
	function __start () {
        
        elapse("webapp.setup");
		
		// 終端処理の登録
		register_shutdown_webapp_function("__end");
		
        // Ajaxr応答への変換処理の登録
        if ($_SERVER["HTTP_X_AJAXR"] || $_REQUEST["__ajaxr"]) {
            
            register_shutdown_webapp_function("shutdown_webapp_for_ajaxr");
        }
		
		// リクエストURLの取得
		registry("Request.request_uri",$_SERVER['REQUEST_URI']);
		
		// 初期設定の適応
		start_webapp();
		
		//-------------------------------------
		// リクエスト情報の解決
		$request_uri =registry("Request.request_uri");
		$document_root_dir =registry("Path.document_root_dir");
		$html_dir =registry("Path.html_dir");
		
		$request_path =url_to_path($request_uri, "index.html");
		$request_file =path_to_file($request_path);
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
			
			report_warning("Request Trouble: Route and File NotFound",registry("Request"));
			
			set_response_code(404);
			
			shutdown_webapp("notfound");
		}

		// レスポンスの設定
		$request_file =registry("Request.request_file");
		registry("Response.template_file", $request_file);
		
		$response_charset =registry("Config.external_charset");
		registry("Response.content_type", 'text/html; charset='.$response_charset);
		
		elapse("webapp.setup",true);
		elapse("webapp.raise_action");
		
		//-------------------------------------
		// Controller/Actionの実行
		$request_page =registry("Request.request_page");
		
		list($controller_name, $action_name) =explode('.',$request_page,2);
		registry(array(
			"Request.controller_name" => $controller_name,
			"Request.action_name" => $action_name,
		));
		
		$controller_obj =raise_action($request_page);
		
		// Controller/Action実行エラー
		if ( ! $controller_obj) {
			
			report_error("Request Routing Error: Controller/Action raise error",registry("Request"));
		}
		
		elapse("webapp.raise_action",true);
		elapse("webapp.fetch_template");
		
		registry("Response.controller_obj", $controller_obj);
		
		//-------------------------------------
		// テンプレートファイルの読み込み
		$template_file =registry("Response.template_file");
		$output =$controller_obj->fetch($template_file);
		
		// 出力
		$content_type =registry("Response.content_type");
		header("Content-type: ".$content_type);
		print($output);
		
		elapse("webapp.fetch_template",true);
		
		shutdown_webapp("normal");
	}

	//-------------------------------------
	// __end
	function __end ($cause, $options) {
		
		if ($cause == "error_report") {
			
			// 異常停止のログを記録
            // cat /var/log/httpd/error_log| grep "RAPP_ERROR" | less
            $msg ="";
            $msg .="RAPP_ERROR ";
            $msg .=($_SERVER["HTTPS"] ? "https" : "http")."://".$_SERVER["SERVER_NAME"]."] : ";
            $msg .=registry("Request.request_uri");
            $msg .=report_template($options["errstr"],$options["params"],
                    $options["options"],$options["backtraces"],array("output_format"=>"plain"));
			$msg .=" | Request = ".decorate_value($_REQUEST,false);
            error_log($msg,0);

			set_response_code(500);
		}
		
		report("WebappLog",array(
			"RequestPath" =>registry("Request.request_path"),
			"RequestPage" =>registry("Request.request_page"),
			"Template" =>registry("Response.template_file"),
			"ShutdownCause" =>$cause,
			"Elapsed" =>elapse(),
			"ResponseState" =>registry("Response.controller_obj"),
		));
	}
