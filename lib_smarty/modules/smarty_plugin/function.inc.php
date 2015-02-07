<?php
	
	//-------------------------------------
	// {{inc path="/element/head.html"}}のサポート
	function smarty_function_inc ($params, $parent_controller) {
		
		$request_path =$params["path"];
		$request_page =path_to_page($request_path);
		$request_file =path_to_file($request_path);
		
		// 静的ページのStaticIncludeControllerへの対応付け
		if ( ! $request_page && file_exists($request_file)) {
			
			$request_page ="static_include.index";
		}
		
		$report_context =array(
			"path" =>$request_path,
			"page" =>$request_page,
			"file" =>$request_file,
		);
		
		// Routing設定もなくHTMLファイルもない場合は404エラー
		if ( ! $request_page && ! file_exists($request_file)) {
			
			report_error("Include Error: Route and File NotFound",$report_context);
		}
		
		$controller =raise_action($request_page, array("parent_controller" =>$parent_controller));
		
		// Controller/Action実行エラー
		if ( ! $controller) {
			
			report_error("Include Routing Error: Controller/Action raise failed",$report_context);
		}
		
		$html =$controller->fetch("path:".$request_path);
		
		return $html;
	}
	
