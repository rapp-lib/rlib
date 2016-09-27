<?php

	function input_type_password ($params, $preset_value, $postset_value, $smarty) {
		
		if ($params["autocomplete"] != "on") {
		
			$params["autocomplete"] ="off";
		}
		
		$params["value"] =isset($postset_value)
				? $postset_value
				: $preset_value;	
		$html =tag("input",$params);
		
		return $html;
	}