<?php

	//-------------------------------------
	// 必須入力
	function rule_required ($value ,$option ){
	
		$value =is_array($value)
				? implode('',$value)
				: $value;
				
		return strlen($value) // && ! preg_match('!^[ 　\n\r]+$!u',$value)
				? false
				: "必ず入力してください";
	}