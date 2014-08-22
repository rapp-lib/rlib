
	function rule_compare ($value, $option) {
		
		$op =$option["op"];
		$target =$option["target"];
		
		if ( ! strlen($value) || ! strlen($target)) {
			
			return false;
		}
		
		if (($op == "<" && $value >= $target) 
				|| ($op == ">" && $value <= $target) 
				|| ($op == "<=" && $value > $target) 
				|| ($op == ">=" && $value < $target)) {
			
			return "値が不正です";
		}
		
		return false;
	}


