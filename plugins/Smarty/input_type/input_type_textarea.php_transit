<?php

	function input_type_textarea ($params, $preset_value, $postset_value, $template) {
		
		$content =isset($postset_value)
				? $postset_value
				: $preset_value;
				
		$op_keys =array(
			"type",
		);
		$attr_html ="";
		
		foreach($params as $key => $value ){
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.str_replace('"','&quot;',$value).'"';
			}
		}
		
		$html ='';
		$html .='<textarea'.$attr_html;
		$html .='>'.$content.'</textarea>';

		return $html;
	}