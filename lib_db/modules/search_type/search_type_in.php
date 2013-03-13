<?php
	
	//-------------------------------------
	// 
	function search_type_in (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		$input =(array)$input;
		
		foreach ($input as $k => $v) {
			
			if ( ! $v) {
				
				unset($input[$k]);
			}
		}
		
		if ( ! $input) {
			
			return null;
		}
		
		// 関連テーブルをSubqueryで間に入れる
		if ($setting["query"]) {
		
			$query =$setting["query"];
			$query["conditions"][] =array($setting["query_target"] => $input);
			return array($target.' IN ('.dbi()->st_select($query).')');
		
		} else {
			
			return array($target =>$input);
		}
		
	}