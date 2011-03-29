<?php

	//-------------------------------------
	// 条件指定
	function rule_assert ($value ,$option) {
		
		return  ! strlen($value) || $option
				? false
				: "入力が不正です"
				;
	}