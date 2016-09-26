<?php
	
	//-------------------------------------
	// ref_array
	function smarty_modifier_ref ($array, $ref_array) {
		
		if (is_string($array)) {
			
			$array =unserialize($array);
		}
		
		return $array && is_array($array)
		 		? ref_array($array,$ref_array)
				: null;
	}