<?php

	//-------------------------------------
	// 文字数制限
	function rule_length ($value ,$option ){

		$length =mb_strlen($value,"UTF-8");
		
		if ( ! $length) {
		
			return false;
		}
		
		list($min,$max) =explode("-",$option);
		
		if( ! ereg('-',$option) ){
		
			return $length == $option
					? false
					: $option."文字で入力してください";
		
		} elseif ( ! strlen($min)) {
		
			return ($length <= $max)
					? false
					: $max."文字までで入力してください";
			
		} elseif( ! strlen($max)) {
		
			return ($length >= $min)
					? false
					: $min."文字以上で入力してください";
				
		} else {
		
			return ($length >= $min && $length <= $max)
					? false
					: $min."文字から".$max."文字までで入力してください";
		}
	}