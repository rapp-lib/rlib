<?php
	
	//-------------------------------------
	// {{inc path="/element/head.html"}}のサポート
	function smarty_function_inc ($params, $smarty_template) {
		
		$vars =(array)$params["vars"];
		
		$request_path =$params["path"];
		$request_page =$params["page"];
		$request_file =$params["file"];
		
		if ($request_path) {
			
			$request_page =path_to_page($request_path);
			$request_file =path_to_file($request_path);
			
		} elseif ($request_page) {
			
			$request_path =page_to_path($request_path);
			$request_file =path_to_file($request_path);
			
		} elseif ($request_file) {
			
			$request_path =file_to_path($request_file);
			$request_page =path_to_page($request_path);
		}
		
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
		
		$parent_controller =$smarty_template->smarty;
		$controller =raise_action($request_page, array("parent_controller" =>$parent_controller));
		
		// Controller/Action実行エラー
		if ( ! $controller) {
			
			report_error("Include Routing Error: Controller/Action raise failed",$report_context);
		}
		
		// テンプレート中で渡された値のAssign
		foreach ($vars as $k => $v) {
			
			if (is_numeric($k)) {
				
				$controller->_tpl_vars[$v] =$parent_controller->tpl_vars[$v]->value;
				
			} elseif (is_string($k)) {
				
				$controller->_tpl_vars[$k] =$v; 
			}
		}
		
		$html =$controller->fetch("file:".$request_file);
		
		return $html;
	}
	
