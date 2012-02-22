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
				// 日付の表示： format="{%l}{%yp}{%mp}{%dp}{%datefix}{%datepick}"
				// 時刻の表示： format="{%l}{%hp}{%ip}{%datefix}"
			"assign", // 部品をアサインするテンプレート変数名
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		// 初期選択値の組み立て
		$d ="";
		
		if ($postset_value_d =longdate($postset_value)) {
			
			$d =$postset_value_d;
			
		} elseif ($preset_value_d =longdate($preset_value)) {
			
			$d =$preset_value_d;
		}
		
		// 年指定の範囲の設定
		$range =$params["range"]
				? $params["range"]
				: "2007~+5";
		list($y1,$y2) =input_type_date_parse_range($range);
		
		if ($d && $d["Y"]) {
			
			if ($y1 > $d["Y"]) {
				
				$y1 =$d["Y"];
			
			} elseif ($y2 < $d["Y"]) {
				
				$y2 =$d["Y"];
			}
		}
		
		// HTML組み立て
		$html =array();
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["elm_id"] ='ELM_'.$html["alias"];
		$html["head"] ='<span id="'.$html["elm_id"].'">';
		$html["foot"] ='</span>';
		$html["l"] ='<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="date"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>';
		
		// 数値のみ
		$html["y"] =input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),$attr_html,""); 
		$html["m"] =input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),$attr_html,"");
		$html["d"] =input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),$attr_html,"");
		$html["h"] =input_type_date_get_select(
				$params['name']."[h]",$d["H"],range(0,23),$attr_html,"");
		$html["i"] =input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),$attr_html,"");
		$html["s"] =input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),$attr_html,"");
		
		// 年月日表記を含むもの
		$html["yp"] =input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),$attr_html,"年");
		$html["mp"] =input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),$attr_html,"月");
		$html["dp"] =input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),$attr_html,"日");
		$html["hp"] =input_type_date_get_select(
				$params['name']."[h]",$d["H"],range(0,23),$attr_html,"時");
		$html["ip"] =input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),$attr_html,"分");
		$html["sp"] =input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),$attr_html,"秒");
		
		// 固定Hidden入力
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
		
		// JS：日付の誤り訂正
		$html["datefix"] ='<script>/*<!--*/ rui.require("rui.datefix",function(){'
				.'rui.Datefix.fix_dateselect("#'.$html["elm_id"].'"); });'
				.' /*-->*/</script>';
				
		// JS：日付選択カレンダーポップUI
		$html["datepick"] .='<script>/*<!--*/ rui.require("jquery.datepick",function(){'
				.'rui.Datepick.impl_dateselect("#'.$html["elm_id"].'",{yearRange:"'.$y1.':'.$y2.'"}); });'
				.' /*-->*/</script>';
		
		$format =$params["format"]
				? $params["format"]
				: '{%l}{%yp}{%mp}{%dp}{%datefix}{%datepick}';
		$html["full"] =$html["head"].str_template_array($format,$html).$html["foot"];
		
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
		$html .='<option value=""></option>';
		
		foreach ($list as $v) {
		
			$selected =(strlen($value) && (int)$v == (int)$value);
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
	