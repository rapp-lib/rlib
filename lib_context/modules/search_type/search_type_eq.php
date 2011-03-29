<?php
	
	//-------------------------------------
	// 
	function search_type_eq (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return $input
				? array($target =>$input)
				: null;
	}