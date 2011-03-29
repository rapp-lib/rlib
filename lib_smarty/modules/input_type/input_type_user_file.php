<?php

	function input_type_file ($params, $preset_value, $postset_value, $smarty) {
			
		$html =tag("input",$params);
		
		return $html;
	}