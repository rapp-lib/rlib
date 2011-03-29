<?php

	//-------------------------------------
	// メールフォーマット入力
	function rule_mail ($value ,$option ){
		
		return  ! strlen($value) || preg_match('/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $value)
			? false
			: "正しいメールアドレスを入力してください"
			;
	}