<?php

	function input_type_radio ($params, $preset_value, $postset_value, $template) {
		
		if ( ! isset($preset_value)) {
		
			return 'error: value attribute required.';
		}
		
		$attr_html ="";
		
		if ((isset($postset_value) && $preset_value == $postset_value)
			|| ( ! isset($postset_value) && $params["checked"] )) {
			
			$params['checked'] ="checked";
			
		} else {
		
			unset($params['checked']);
		}
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$html ='';
		$html .=(
			'<input'
			.' type="radio"'
			.' value="'.$preset_value.'"'
			.$attr_html
			.' />'
		);

		return $html;
	}