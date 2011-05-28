<?php

	function smarty_modifier_date ($string ,$format="Y/m/d" ) {
		
		if (is_numeric($string)) {
			
			$string =date("Y/m/d H:i:s",$string);
		}
		
		$longdate =longdate($string);
		
		if ( ! $longdate) {
		
			return "";
		}
		
		return preg_replace(
				'!('.implode('|',array_keys($longdate)).')!e',
				'$longdate["$1"]',
				$format);
	}
	