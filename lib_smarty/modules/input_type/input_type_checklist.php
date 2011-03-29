<?php

	function input_type_checklist ($params, $preset_value, $postset_value, $template) {
		
		$op_keys =array(
			"type",
			"name",
			"prefix",
			"postfix",
			"options",
			"options_param",
			"hidezero",
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
		
		$i =0;
		
		$html ='';
		$html .='<!-- checklist -->'."\n";
		$html .='<input type="hidden"';
		$html .=' name="'.$params["name"].'['.($i++).']"';
		$html .=' value="__CSV__" />'."\n";
		
		$values =isset($postset_value)
			? explode(',',$postset_value)
			: explode(',',$preset_value)
			;
		
		$elements =array();
		
		foreach ($options as $option_value => $option_label) {
			
			if ($params['hidezero'] && $option_value===0) {
			
				continue;
			}
			
			$input_element ="";
			$input_element .=$params["prefix"];
			$input_element .='<input'.$attr_html;
			$input_element .=' type="checkbox"';
			$input_element .=' name="'.$params["name"].'['.($i++).']"';
			$input_element .=' value="'.$option_value.'"';
			
			if (in_array((string)$option_value,$values,true)) {
				
				$input_element .=' checked="checked"';
			}
			
			$input_element .=' />';
			
			$label_element =$option_label;
			$postfix_element =$params["postfix"]."\n";
			
			$sub_html =$input_element;
			$sub_html .=$label_element;
			$sub_html .=$postfix_element;
			
			$elements[] =array(
					"sub_html" =>$sub_html,
					"input" =>$input_element,
					"label" =>$label_element);
			
			$html .=$sub_html;
		}

		$html .='<!-- /checklist -->';
		
		return $html;
	}