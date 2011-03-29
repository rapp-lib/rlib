<?php

	function input_type_select ($params, $preset_value, $postset_value, $template) {
		
		$selected_value =isset($postset_value)
				? $postset_value
				: $preset_value;
		
		$op_keys =array(
			"type",
			"options",
			"options_param",
			"hidezero",
			"zerooption",
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		if (is_array($params["options"])) {
			
			$options =$params["options"];
		
		} else {
		
			$list_options =obj("ListOptions")->get_instance($params["options"]);
			$options =$list_options->options($params["options_param"]);
		}
		
		if (isset($params["zerooption"])) {
		
			$options =array("" =>$params["zerooption"]) + $options;
		}
		
		$options_html ="";
		
		foreach ($options as $option_value => $option_label) {
			
			if ($params['hidezero'] && $option_value=="") {
			
				continue;
			}
			
			$options_html .='<option value="'.$option_value.'"';
			
			if ((string)$option_value == (string)$selected_value) {
				
				$options_html .=' selected="selected"';
			}
			
			$options_html .='>'.$option_label.'</option>'."\n";
		}
		
		$html ='';
		$html .='<select'.$attr_html;
		$html .='>'."\n";
		$html .=$options_html;
		$html .='</select>';

		return $html;
	}