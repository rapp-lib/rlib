<?php

	//-------------------------------------
	// 日付入力
	function rule_date ($value ,$option) {
		
		if (preg_match('!^(\d+)[/-]+(\d+)[/-](\d+)$!',$value,$match)) {
		
			list(,$year,$month,$date) =$match;
			$is_valid_date =checkdate($month,$date,$year);
		}
		
		return  ! strlen($value) || $is_valid_date
			? false
			: "正しい日付を入力してください"
			;
	}