<?php

	//-------------------------------------
	// 整数入力
	function rule_integer ($value ,$option ){
		
		return  ! strlen($value) || ctype_digit(preg_replace('!^-!','',$value))
			? false
			: "整数で入力してください"
			;
	}