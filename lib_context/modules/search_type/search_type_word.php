<?php
	
	//-------------------------------------
	// 
	function search_type_word (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		$part_query =array();
		
		foreach (preg_split('![\s　]+!u',$input) as $keyword) {
			
			if ($keyword) {
			
				$part_query[] =array($target." LIKE " =>"%".$keyword."%");
			}
		}
		
		$part_query =count($part_query) == 1
				? $part_query[0]
				: $part_query;
		
		return $part_query;
	}