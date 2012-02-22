<?php

	function input_type_checkbox ($params, $preset_value, $postset_value, $template) {
		
		if ( ! strlen($preset_value)) {
		
			return 'error: value attribute required.';
		}
	
		if ((strlen($postset_value) && $preset_value == $postset_value)
				|| ( ! strlen($postset_value) && $params["checked"])) {
			
			$params['checked'] ="checked";
			
		} else {
		
			unset($params['checked']);
		}
		
		$op_keys =array(
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$html ='';
		$html .=(
			'<input'
			.' type="hidden"'
			.' name="'.$params['name'].'"'
			.' value=""'
			.' />'."\n"
		);
		$html .=(
			'<input'
			.' type="checkbox"'
			.' value="'.$preset_value.'"'
			.$attr_html
			.' />'
		);

		return $html;
	}