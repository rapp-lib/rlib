<?php
	
	//-------------------------------------
	// 
	function search_type_eq (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		$module =load_module("search_type","in",true);
		return call_user_func_array($module,array(
				$name,
				$target,
				$input,
				$setting,
				$context));
	}