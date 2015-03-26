<?php
	function rule_compare_date ($value, $option) {
		
		$op =$option["op"];
		$target =$option["target_value"];
		
		if ( ! strlen($value) || ! strlen($target)) {
			
			return false;
		}
		
		$value =strtotime($value);
		$target =strtotime($target);
		
		if (($op == "<" && $value >= $target) 
				|| ($op == ">" && $value <= $target) 
				|| ($op == "<=" && $value > $target) 
				|| ($op == ">=" && $value < $target)) {
			
			return "値が不正です";
		}
		
		return false;
	}


