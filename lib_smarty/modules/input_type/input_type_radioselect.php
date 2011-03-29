<?php

	function input_type_radioselect ($params, $preset_value, $postset_value, $template) {
		
		$selected_value =isset($postset_value)
				? $postset_value
				: $preset_value;
		
		$op_keys =array(
			"type",
			"prefix",
			"postfix",
			"options",
			"options_param",
			"hidezero",
			"format",
			"format_for",
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
		
		$html ='';
		$html .='<!-- radioselect -->'."\n";
		
		$is_first =1;
		
		$elements =array();
		$sub_htmls =array();
		
		foreach ($options as $option_value => $option_label) {
			
			if ($params['hidezero'] && $option_value===0) {
			
				continue;
			}
			
			$input_element ="";
			$input_element .=$params["prefix"];
			$input_element .='<input'.$attr_html;
			$input_element .=' type="radio" value="'.$option_value.'"';
			
			if ($option_value == $selected_value || $is_first) {
				
				$input_element .=' checked="checked"';
				$is_first=0;
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
			$sub_htmls[] =$sub_html;
		}
		
		if (isset($params["format"])) {
		
			$format_html =$template->get_share_var("format",$params["format"]);
			$format_for =$params["format_for"];
			
			if ($format_for>0) {
			
				for ($i=0; $i<count($elements); $i+=$format_for) {
					
					$replace_vars =array();
					
					foreach (array_slice($elements,$i,$format_for) as $j => $element) {
						
						$replace_vars[$j] =$element["sub_html"];
						$replace_vars[$j.":label"] =$element["label"];
						$replace_vars[$j.":input"] =$element["input"];
					}
					
					$html .=format_string($format_html,$replace_vars);
				}	
			
			} else {
			
				$html .=format_string($format_html,$sub_htmls);
			}
			
		} else {
		
			$html .=implode("",$sub_htmls);
		}

		$html .='<!-- /radioselect -->';
		
		return $html;
	}