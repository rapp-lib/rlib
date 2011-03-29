<?php

	function input_type_datetext (
			$params, 
			$preset_value, 
			$postset_value, 
			$template) {
		
		$params["value"] =isset($postset_value)
				? $postset_value
				: $preset_value;
				
		$op_keys =array(
			"type",
			"name",
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$html ='';
		$html .='<!-- datetext -->'."\n";
		
		// 型[0]
		$html .='<input type="hidden" name="'.$params['name'].'[0]" '.
				' value="__DATETEXT__" />'."\n";
				
		// 入力[1]
		$html .='<input type="text" name="'.$params['name'].'[1]"'.$attr_html.' />'."\n";
				
		$html .='<!-- /datetext -->'."\n";
		
		return $html;
	}