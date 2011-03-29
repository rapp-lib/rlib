<?php

	function input_type_dateselect (
			$params, 
			$preset_value, 
			$postset_value, 
			$template) {
		
		$op_keys =array(
			"type",
			"name",
			"range",
			"zeroselect",
			"yaer_zeroselect",
			"year_fixed",
			"month_fixed",
			"date_fixed",
			"hour_input",
			"minute_input",
			"minute_interval",
			"sec_input",
			"sec_interval",
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$selected_date_struct =getdate();
		$year_selected =$selected_date_struct["year"];
		$month_selected =$selected_date_struct["mon"];
		$date_selected =$selected_date_struct["mday"];
		$hours_selected =$selected_date_struct["hours"];
		$minutes_selected =$selected_date_struct["minutes"];
		$secs_selected =$selected_date_struct["second"];
		$use_default =true;
			
		if ($longdate =longdate($postset_value)) {
		
			$year_selected =$longdate["y"];
			$month_selected =$longdate["m"];
			$date_selected =$longdate["d"];
			$hours_selected =$longdate["h"];
			$minutes_selected =$longdate["i"];
			$secs_selected =$longdate["s"];
			$use_default =false;
			
		} elseif ($longdate =longdate($preset_value)) {
		
			$year_selected =$longdate["y"];
			$month_selected =$longdate["m"];
			$date_selected =$longdate["d"];
			$hours_selected =$longdate["h"];
			$minutes_selected =$longdate["i"];
			$secs_selected =$longdate["s"];
			$use_default =false;
		}
		
		$year_start =date("Y");
		$year_end =date("Y");
		
		// 年範囲指定
		$range_pattern ='!(([\+\-]?)(\d+))?~(([\+\-]?)(\d+))?!';
		
		if (preg_match($range_pattern,$params["range"],$match)) {
			
			if (strlen($match[1])) {
			
				if ($match[2] == '+') {
				
					$year_start +=$match[3];
					
				} elseif ($match[2] == '-') {
				
					$year_start -=$match[3];
					
				} else {
				
					$year_start =$match[3];
				}
			}
			
			if (strlen($match[4])) {
			
				if ($match[5] == '+') {
				
					$year_end +=$match[6];
					
				} elseif ($match[5] == '-') {
				
					$year_end -=$match[6];
					
				} else {
				
					$year_end =$match[6];
				}
			}
		}
		
		$html ='';
		$html .='<!-- dateselect -->'."\n";
		$html .='<span id="ds_'.$params['name'].'_area">';
		
		// 型[0]
		$html .='<input type="hidden" name="'.$params['name'].'[0]" '.
				' value="__DATESELECT__"'.$attr_html.' />'."\n";
				
		// 年[1]
		if (isset($params["year_fixed"])) {
		
			$html .='<input type="hidden" name="'.$params['name'].'[1]"'.
					' value="'.$params["year_fixed"].'"'.$attr_html.' />'."\n";
		
		} else {
		
			$html .='<select name="'.$params['name'].'[1]"'.
					$attr_html.'>'."\n";
			
			if ($params["yaer_zeroselect"]) {
				
				$html .='<option value=""'.
						(strlen($year_selected)==0
						? ' selected="selected"' : '').
						'>'.$params["yaer_zeroselect"].'</option>'."\n";
			}
			
			foreach (range($year_start,$year_end) as $option) {
			
				$html .='<option value="'.$option.'"'.
						(($option==$year_selected) 
						? ' selected="selected"' : '').
						'>'.$option.'年</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		// 月[2]
		if (isset($params["month_fixed"])) {
		
			$html .='<input type="hidden" name="'.$params['name'].'[2]"'.
					' value="'.$params["month_fixed"].'"'.$attr_html.' />'."\n";
		
		} else {
		
			$html .='<select name="'.$params['name'].'[2]"'.
					$attr_html.'>'."\n";
		
			foreach (range(1,12) as $option) {
			
				$html .='<option value="'.$option.'"'.
						(($option==$month_selected) ? ' selected="selected"' : '').
						'>'.$option.'月</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		// 日[3]
		if (isset($params["date_fixed"])) {
		
			$html .='<input type="hidden" name="'.$params['name'].'[3]"'.
					' value="'.$params["date_fixed"].'"'.$attr_html.' />'."\n";
		
		} else {
		
			$html .='<select name="'.$params['name'].'[3]"'.
					$attr_html.'>'."\n";
		
			foreach (range(1,31) as $option) {
				
				$html .='<option value="'.$option.'"'.
						(($option==$date_selected) 
						? ' selected="selected"' : '').
						'>'.$option.'日</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		// 時[4]
		if (isset($params["hour_input"])) {
		
			$html .='<select name="'.$params['name'].'[4]"'.
					$attr_html.'>'."\n";
		
			foreach (range(0,23) as $option) {
				
				$html .='<option value="'.$option.'"'.
						(($option==$hours_selected) ? ' selected="selected"' : '').
						'>'.$option.'時</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		// 分[5]
		if (isset($params["minute_input"])) {
		
			$html .='<select name="'.$params['name'].'[5]"'.
					$attr_html.'>'."\n";
		
			foreach (range(0,59) as $option) {
				
				if ($params["minute_interval"]) {
					
					if ($option%$params["minute_interval"] != 0) {
						
						continue;
					}
				}
				
				$html .='<option value="'.$option.'"'.
						(($option==$minutes_selected) ? ' selected="selected"' : '').
						'>'.$option.'分</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		// 時:分[5]
		if (isset($params["hour_minute_input"])) {
		
			$html .='<select name="'.$params['name'].'[5]"'.
					$attr_html.'>'."\n";
		
			foreach (range(0,23) as $option_h) {
							
				foreach (range(0,59) as $option_m) {
					
					if ($params["minute_interval"]) {
						
						if ($option%$params["minute_input"] != 0) {
							
							continue;
						}
					}
					
					$is_selected =$option_h==$hours_selected 
							&& $option_m==$minutes_selected
							? ' selected="selected"'
							: "";
							
					$html .='<option value="'.$option_h.':'.$option_m.'"'.
							$is_selected.
							'>'.$option_h.':'.$option_m.'</option>'."\n";
				}
			}
		
			$html .='</select>'."\n";
		}
		
		// 秒[7]
		if (isset($params["sec_input"])) {
		
			$html .='<select name="'.$params['name'].'[7]"'.
					$attr_html.'>'."\n";
		
			foreach (range(0,59) as $option) {
				
				if ($params["sec_interval"]) {
					
					if ($option%$params["sec_interval"] != 0) {
						
						continue;
					}
				}
				
				$html .='<option value="'.$option.'"'.
						(($option==$secs_selected) ? ' selected="selected"' : '').
						'>'.$option.'秒</option>'."\n";
			}
		
			$html .='</select>'."\n";
		}
		
		$html .='</span>';
		
		// 空選択[6]
		$html .='<input type="hidden"'.
					' name="'.$params['name'].'[6]" value="0"'.$attr_html.' />'."\n";
					
		if ($params['zeroselect']) {
			
			$html .='<input type="checkbox" name="'.$params['name'].'[6]"'.
					' value="1" id="ds_'.$params['name'].'_checkbox"'.
					' onclick="var t=document.getElementById('.
					'\'ds_'.$params['name'].'_area\');t.style.display'.
					'=this.checked'.' ? \'none\' : \'inline\';" />'.
					$params['zeroselect']."\n";
			
			if ($use_default) {
			
				$html .='<script> var ds_'.$params['name'].'_checkbox'.
						' =document.getElementById('.
						'\'ds_'.$params['name'].'_checkbox\');'.
						' ds_'.$params['name'].'_checkbox.checked =true;'.
						' ds_'.$params['name'].'_checkbox.onclick();'.
						'</script>'."\n";
			}
		}
		
		$html .='<!-- /dateselect -->'."\n";
		
		return $html;
	}