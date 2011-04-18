<?php
	
	//-------------------------------------
	// 
	function search_type_compare (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		$op =$setting["op"]
				? $setting["op"]
				: "=";
		
		return $input
				? array($target.' '.$op =>$input)
				: null;
	}