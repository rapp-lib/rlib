<?php
	/*
		下限上限を指定して、入力値が収まるかどうか判定
			下限： $setting["target_start"]
			上限： $setting["target_end"]
	*/
	
	//-------------------------------------
	// 
	function search_type_new (
			$name,
			$target,
			$input,
			$setting,
			$context) {
			
		return $input
				? array($target.' >=' => longdate_format(strtotime("-5 day"), "Y/m/d"))
				: null;
	}