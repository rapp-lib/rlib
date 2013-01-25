<?php
	
	function smarty_modifier_trunc ($value, $length, $append="...") {
		
		return mb_strlen($value)>$length
				? mb_substr($value,0,$length).$append
				: $value;
	}
	