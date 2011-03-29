<?php
	
	//-------------------------------------
	// 
	function search_type_like (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return $input
				? array($target." LIKE " =>"%".$input."%")
				: null;
	}