<?php
	
	function smarty_modifier_tostring ($value, $delim) {
		
		return is_array($value)
				? implode($value,$delim)
				: (string)$value;
	}
	