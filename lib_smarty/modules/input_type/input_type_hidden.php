<?php

	function input_type_hidden ($params, $preset_value, $postset_value, $smarty) {
		
		$params["value"] =$preset_value;	
		$html =tag("input",$params);
		
		return $html;
	}