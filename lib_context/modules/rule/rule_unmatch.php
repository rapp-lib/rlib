<?php

	//-------------------------------------
	// 一致チェック
	function rule_unmatch ($value ,$option ){
		
		if ( strcmp($value,$option) == 0 ){
			return false;
		}
		return "一致していません";
	}