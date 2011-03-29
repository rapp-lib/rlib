<?php

	//-------------------------------------
	// URL入力
	function rule_url ($value ,$option ){
		
		return  ! strlen($value) || preg_match('/^https?:\/\/[-_.!~*\''.
				'()a-zA-Z0-9;\/?:\@&=+\$,%#]+$/', $value)
			? false
			: "正しいURLを入力してください"
			;
	}