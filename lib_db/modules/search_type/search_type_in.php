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
		
		return $input
				? array($target =>$input)
				: null;
	}