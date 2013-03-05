<?php
	
	//-------------------------------------
	// 
	function search_type_eq (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return (is_string($input) && strlen($input)) || $input
				? array($target =>$input)
				: null;
	}