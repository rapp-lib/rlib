<?php
	
	//-------------------------------------
	// array_slice
	function smarty_modifier_slice ($array, $offset, $length=null) {
		
		if (is_string($array)) {
			
			$array =unserialize($array);
		}
		
		if ( ! $array || ! is_array($array)) {
		 	
			return array();
		}
		
		return $length !== null
				? array_slice($array, $offset, $length)
				: array_slice($array, $offset);
	}