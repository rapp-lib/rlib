<?php

	function input_type_tel ($params, $preset_value, $postset_value, $smarty) {
		
		$params["value"] =isset($postset_value)
				? $postset_value
				: $preset_value;	
		$html =tag("input",$params);
		
		return $html;
	}