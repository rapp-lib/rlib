<?php

	//-------------------------------------
	// 郵便番号入力
	function rule_postcode ($value ,$option ){
		
		return  ! strlen($value) || preg_match('!(\d\d\d)-?(\d\d\d\d)!',$value,$match)
			? false
			: "半角数字(ハイフンあり可)で入力してください"
			;
	}