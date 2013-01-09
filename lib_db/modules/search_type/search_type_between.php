<?php
	/*
		下限上限を指定して、入力値が収まるかどうか判定
			下限： $setting["target_start"]
			上限： $setting["target_end"]
	*/
	
	//-------------------------------------
	// 
	function search_type_between (
			$name,
			$target,
			$input,
			$setting,
			$context) {
		
		return $input
				? array($setting["target_start"].' <='=> $input,
			    		$setting["target_end"].' >='=> $input)
				: null;
	}