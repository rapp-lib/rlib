<?php

	//-------------------------------------
	// 半角英数字入力
	function rule_alphanum ($value ,$option ){
		
		return  ! strlen($value) || ctype_alnum($value)
			? false
			: "半角英数字で入力してください"
			;
	}