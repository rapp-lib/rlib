<?php

	//-------------------------------------
	// メールフォーマット入力
	function rule_mail_left ($value, $option, $key, $context){
		
		return  (! strlen($value)) || preg_match('/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $value."@docomo.ne.jp")
			? false
			: "正しいメールアドレスを入力してください"
			;
	}