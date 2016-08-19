<?php
	
	//-------------------------------------
	// 
	function search_type_in (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		// 配列として整形
		$input =is_array($input)
				? $input
				: array($input);
			
		// 空の要素の除外
		foreach ($input as $k => $v) {
			
			if ( ! (is_string($v) && strlen($v)) && ! $v) {
				
				unset($input[$k]);
			}
		}
		
		// 検索条件がない場合はnull
		if ( ! $input) {
			
			return null;
		}
		
		// target IN (...query... AND query_target=input)
		if ($setting["query"] && $setting["query_target"]) {
		
			$query =$setting["query"];
			$query["conditions"][] =array($setting["query_target"] => $input);
			return array($target.' IN ('.dbi()->st_select($query).')');
		
		// EXISTS (...query... AND target=input)
		} elseif ($setting["query"]) {
		
			$query =$setting["query"];
			$query["conditions"][] =array($target => $input);
			return array('EXISTS ('.dbi()->st_select($query).')');
		
		// target IN input
		} else {
			
			return array($target =>$input);
		}
	}