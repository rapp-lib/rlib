<?php
	
	//-------------------------------------
	// {{inc path="/element/head.html"}}のサポート
	function smarty_function_inc ($params, &$smarty) {
		
		$path =$params["path"];
		$page =path_to_page($path);
		$controller_obj =raise_action($page, $smarty);
		//$html =$controller_obj->fetch("path:".$path);
		$html =$smarty->fetch("path:".$path);
		
		return $html;
	}
	
