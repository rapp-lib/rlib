<?php
		
	//-------------------------------------
	// serializeされたデータの一部との重複部分を検索
	function search_type_intersect (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		/*
			contains_all
				true:  入力値すべてを内包するならば真
				false: 選択肢のうちに一部でも重複があるならば真
		*/
		$op =$setting["contains_all"]
				? "and"
				: "or";
				
		$query_list =array();
		
		foreach ((array)$input as $k => $v) {
			
			if ( ! $v) {
				
				continue;
			}
			
			$item =serialize($k).serialize($v);
			$query_list[] =array($target.' LIKE' =>'%'.$item.'%');
		}
		 
		return $query_list
				? array($op =>$query_list)
				: null;
	}