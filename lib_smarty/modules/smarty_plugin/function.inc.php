<?php
	
	//-------------------------------------
	// {{inc path="/element/head.html"}}のサポート
	function smarty_function_inc ($params, $smarty_template) {
		
		$vars =(array)$params["vars"];
		$request_path =$params["path"];
		$request_page =$params["page"];
		$request_file =$params["file"];
		
        // file="path:..."指定
		if ($request_file && preg_match('!^path:(.*?)$!',$request_file,$match)) {
			
			$request_path =$match[1];
			$request_file =null;
		
		} elseif ($request_file && preg_match('!^page:(.*?)$!',$request_file,$match)) {
			
			$request_page =$match[1];
			$request_file =null;
		}
		
        // path指定の解決
		if ($request_path) {
			
			$request_page =path_to_page($request_path);
			$request_file =path_to_file($request_path);
			
        // page指定の解決
		} elseif ($request_page) {
			
			$request_path =page_to_path($request_path);
			$request_file =path_to_file($request_path);
            
        } else {
			
			report_error("Include Error: Invalid path or page",array(
    			"path" =>$request_path,
    			"page" =>$request_page,
    			"file" =>$request_file,
    		));
		}
		
		// 静的ページのStaticIncludeControllerへの対応付け
		if ( ! $request_page && file_exists($request_file)) {
			
			$request_page ="static_include.index";
		}
		
		// Routing設定もなくHTMLファイルもない場合は404エラー
		if ( ! $request_page && ! file_exists($request_file)) {
			
			report_error("Include Error: Route and File NotFound",array(
    			"path" =>$request_path,
    			"page" =>$request_page,
    			"file" =>$request_file,
    		));
		}
		
		$controller =raise_action($request_page, array(
            "parent_controller" =>$smarty_template->smarty,
            "parent_smarty_template" =>$smarty_template,
            "vars" =>$vars,
        ));
		
		// Controller/Action実行エラー
		if ( ! $controller) {
			
			report_error("Include Routing Error: Controller/Action raise failed",array(
    			"path" =>$request_path,
    			"page" =>$request_page,
    			"file" =>$request_file,
    		));
		}
		
		// テンプレート中で渡された値のAssign
		foreach ($vars as $k => $v) {
			
			if (is_numeric($k)) {
				
				$controller->_tpl_vars[$v] =$smarty_template->tpl_vars[$v]->value;
				
			} elseif (is_string($k)) {
				
				$controller->_tpl_vars[$k] =$v; 
			}
		}
		
		return $controller->fetch("file:".$request_file);
	}
	
