<?php

	//-------------------------------------
	// 実数入力
	function rule_real ($value ,$option ){
		
		return  ! strlen($value) || ctype_digit(preg_replace('!(^-|\.)!','',$value))
			? false
			: "実数値で入力してください"
			;
	}