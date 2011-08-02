<?php
	
	//-------------------------------------
	// 
	function search_type_function (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return call_user_func_array($setting["function"],array(
			$name,
			$target,
			$input,
			$setting,
			$context
		));
	}