<?php
	
	//-------------------------------------
	// 
	function search_type_like (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return strlen($input)
				? array($target." LIKE " =>"%".$input."%")
				: null;
	}