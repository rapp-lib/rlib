<?php

	function input_type_checkbox ($params, $preset_value, $postset_value, $template) {
			
		if (strlen($params["options"])) {
		
			$list_options =obj("ListOptions")->get_instance($params["options"]);
			
			foreach ($list_options->options() as $k => $v) {
			
				if ($list_options->is_selected($k)) {
					
					$preset_value =$k;
				
				} else {
				
					$params['subvalue'] =$k;
				}
			}
			
			unset($params['options']);
		}
		
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
			"subvalue"
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$html ='';
		
		if (isset($params['subvalue'])) {
			
			$html .=(
				'<input'
				.' type="hidden"'
				.' name="'.$params['name'].'"'
				.' value="'.$params['subvalue'].'"'
				.' />'."\n"
			);
		}
		
		$html .=(
			'<input'
			.' type="checkbox"'
			.' value="'.$preset_value.'"'
			.$attr_html
			.' />'
		);

		return $html;
	}