<?php

	//-------------------------------------
	// 数字入力
	function rule_number ($value ,$option ){
		
		return  ! strlen($value) || ctype_digit($value)
			? false
			: "数字のみで入力してください"
			;
	}