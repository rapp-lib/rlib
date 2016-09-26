<?php
	
	function smarty_modifier_which ($value, $if_true, $if_false=null) {
		
		return $value 
				? $if_true
				: $if_false;
	}
	