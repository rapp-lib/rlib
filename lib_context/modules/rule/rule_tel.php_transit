<?php

	//-------------------------------------
	// 整数入力
	function rule_tel ($value ,$option ){
		
		return  ! strlen($value) || ctype_digit(preg_replace('!-!','',$value))
			? false
			: "半角数字(ハイフンあり可)で入力してください"
			;
	}