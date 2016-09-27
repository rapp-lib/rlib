<?php

	//-------------------------------------
	// 正規表現制限
	function rule_regex ($value ,$option ){
		
		return  ! strlen($value) || preg_match($option,$value)
			? false
			: "正しい形式で入力してください"
			;
	}