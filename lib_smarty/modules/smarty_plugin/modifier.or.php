<?php
	
	function smarty_modifier_or () {
		
		$args =func_get_args();
		
		foreach ($args as $arg) {
			
			if ($arg) {
				
				return $arg;
			}
		}
		
		return null;
	}
	