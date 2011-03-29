<?php

	//-------------------------------------
	// 必須入力
	function rule_required ($value ,$option ){
		
		return strlen($value)
			? false
			: "必ず入力してください"
			;
	}