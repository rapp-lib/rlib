<?php

	function input_type_date (
			$params, 
			$preset_value, 
			$postset_value, 
			$template) {
		
		// Attrsの組み立て
		$op_keys =array(
			"type",
			"name",
			"range", // 年の範囲指定（例："1970~+5"）
			"format", // y/m/d/h/i/sについて「{%y}{%yp}{%yf}」のように指定する
			"assign", // 部品をアサインするテンプレート変数名
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		// 初期選択値の組み立て
		$d =getdate();
		
		foreach (array("year" =>"y","mon" =>"m","mday" =>"d",
				"hours" =>"h","minutes" =>"i","second" =>"s") as $k => $v) {
		
			$d[$v] =$d[$k]; 
			unset($d[$k]);
		}
		
		if ($postset_value_d =longdate($postset_value)) {
			
			$d =$postset_value_d;
			
		} elseif ($preset_value_d =longdate($preset_value)) {
			
			$d =$preset_value_d;
		}
		
		$range =$params["range"]
				? $params["range"]
				: "-5~+5";
		list($y1,$y2) =input_type_date_parse_range($range);
		
		// HTML組み立て
		$html =array();
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["l"] ='<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="date"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>';
		$html["y"] =input_type_date_get_select(
				$params['name']."[y]",$d["y"],range($y1,$y2),$attr_html,"");
		$html["m"] =input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),$attr_html,"");
		$html["d"] =input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),$attr_html,"");
		$html["h"] =input_type_date_get_select(
				$params['name']."[h]",$d["h"],range(0,23),$attr_html,"");
		$html["i"] =input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),$attr_html,"");
		$html["s"] =input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),$attr_html,"");
		$html["yp"] =input_type_date_get_select(
				$params['name']."[y]",$d["y"],range($y1,$y2),$attr_html,"年");
		$html["mp"] =input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),$attr_html,"月");
		$html["dp"] =input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),$attr_html,"日");
		$html["hp"] =input_type_date_get_select(
				$params['name']."[h]",$d["h"],range(0,23),$attr_html,"時");
		$html["ip"] =input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),$attr_html,"分");
		$html["sp"] =input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),$attr_html,"秒");
		$html["yf"] ='<input type="hidden"'
				.' name="'.$params['name'].'[y]'.'"'.' value="1970"/>';
		$html["mf"] ='<input type="hidden"'
				.' name="'.$params['name'].'[m]'.'"'.' value="1"/>';
		$html["df"] ='<input type="hidden"'
				.' name="'.$params['name'].'[d]'.'"'.' value="1"/>';
		$html["hf"] ='<input type="hidden"'
				.' name="'.$params['name'].'[h]'.'"'.' value="0"/>';
		$html["if"] ='<input type="hidden"'
				.' name="'.$params['name'].'[i]'.'"'.' value="0"/>';
		$html["sf"] ='<input type="hidden"'
				.' name="'.$params['name'].'[s]'.'"'.' value="0"/>';
		
		$html["full"] =$params["format"]
				? $params["format"]
				: '{%l}{%y}{%m}{%d}{%hf}{%if}{%sf}';
		$html["full"] =str_template_array($html["full"],$html);
		
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$ref =& ref_array($template->_tpl_vars,$params["assign"]);
			$ref =$html;
		}
		
		return $html["full"];
	}

	function input_type_date_get_select ($name, $value, $list, $attrs, $postfix) {
	
		$html ="";
		$html .='<select name="'.$name.'"'.$attrs.'>';
		
		foreach ($list as $v) {
		
			$selected =((int)$v == (int)$value);
			$html .='<option value="'.$v.'"'
					.($selected ? ' selected="selected"' :'')
					.'>'.$v.$postfix.'</option>';
		}
		
		$html .='</select>';
		
		return $html;
	}

	function input_type_date_parse_range ($range) {
	
		$year_start =date("Y");
		$year_end =date("Y");
		
		// 年範囲指定
		$range_pattern ='!(([\+\-]?)(\d+))?~(([\+\-]?)(\d+))?!';
		
		if (preg_match($range_pattern,$range,$match)) {
			
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
		
		return array($year_start,$year_end);
	}
	