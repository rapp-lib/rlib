<?php

	//-------------------------------------
	// 必ずエラーを発行する
	function rule_error ($value ,$option) {
		
		return $option ? "入力が不正です" : false;
	}