<?php
	
	//-------------------------------------
	// 
	function search_type_query (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return $input
				? $target
				: null;
	}