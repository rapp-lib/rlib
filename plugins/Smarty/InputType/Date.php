<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 * 
 */
class Date extends BaseInput
{
	/**
	 * @override
	 */
	public function __construct ($value, $attrs) 
	{
		list($params,$attrs) =$this->filterAttrs($attrs,array(
			"type",
			"name",
			"range", // 年の範囲指定（例："1970~+5"）
			"format", // y/m/d/h/i/sについて「{%y}{%yp}{%yf}」のように指定する
				// 日付の表示： format="{%l}{%yp}{%mp}{%dp}{%datefix}{%datepick}"
				// 時刻の表示： format="{%l}{%hp}{%ip}{%datefix}"
			"assign", // 部品をアサインするテンプレート変数名
		));

		// HTML属性
		$attr_html ="";
		$attr_html_part =array();
		foreach ($attrs as $k => $v) {
			$attr_html .=' '.$k.'="'.$v.'"';
		}
		foreach ($params as $k => $v) {
			if (preg_match('!^([ymdhisx])_(.+)$!',$k,$match)) {
				$attr_html_part[$match[1]] .=' '.$match[2].'="'.$v.'"';
				unset($params[$k]);
			}
		}
		
		// 初期選択値の組み立て
		$d =array();
		
		if ($value !== null) {
			
			$value =$this->input_type_date_parse_value($value);
			$d =longdate($postset_value);
		}

        $d["y"] =$d["Y"];
        
		// 年指定の範囲の設定
		$range =$params["range"]
				? $params["range"]
				: "2007~+5";
		list($y1,$y2) =input_type_date_parse_range($range);
		
		// 入力されている年数を含むように範囲を調整
		if ($d && $d["y"]) {
			
			if ($y1 < $y2) {
			
				if ($y1 > $d["y"]) {
					
					$y1 =$d["y"];
				
				} elseif ($y2 < $d["y"]) {
					
					$y2 =$d["y"];
				}
			
			} elseif ($y1 < $y2) {
			
				if ($y2 > $d["y"]) {
					
					$y2 =$d["y"];
				
				} elseif ($y1 < $d["y"]) {
					
					$y1 =$d["y"];
				}
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
		$html["y"] =$this->input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),
				$attr_html.$attr_html_part["y"],""); 
		$html["m"] =$this->input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),
				$attr_html.$attr_html_part["m"],"");
		$html["d"] =$this->input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),
				$attr_html.$attr_html_part["d"],"");
		$html["h"] =$this->input_type_date_get_select(
				$params['name']."[h]",$d["H"],range(0,23),
				$attr_html.$attr_html_part["h"],"");
		$html["i"] =$this->input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),
				$attr_html.$attr_html_part["i"],"");
		$html["s"] =$this->input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),
				$attr_html.$attr_html_part["s"],"");
        
		// 年月日表記を含むもの
		$html["yp"] =$this->input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),
				$attr_html.$attr_html_part["y"],"年");
		$html["mp"] =$this->input_type_date_get_select(
				$params['name']."[m]",$d["m"],range(1,12),
				$attr_html.$attr_html_part["m"],"月");
		$html["dp"] =$this->input_type_date_get_select(
				$params['name']."[d]",$d["d"],range(1,31),
				$attr_html.$attr_html_part["d"],"日");
		$html["hp"] =$this->input_type_date_get_select(
				$params['name']."[h]",$d["H"],range(0,23),
				$attr_html.$attr_html_part["h"],"時");
		$html["ip"] =$this->input_type_date_get_select(
				$params['name']."[i]",$d["i"],range(0,59),
				$attr_html.$attr_html_part["i"],"分");
		$html["sp"] =$this->input_type_date_get_select(
				$params['name']."[s]",$d["s"],range(0,59),
				$attr_html.$attr_html_part["s"],"秒");
        
        // 和暦表記年
		$html["yw"] =$this->input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),
				$attr_html.$attr_html_part["y"],"",array("year_format" =>"wareki")); 
		$html["ywh"] =$this->input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),
				$attr_html.$attr_html_part["y"],"",array("year_format" =>"wareki_heiki")); 
        $html["ysh"] =$this->input_type_date_get_select(
				$params['name']."[y]",$d["Y"],range($y1,$y2),
				$attr_html.$attr_html_part["y"],"",array("year_format" =>"seireki_heiki")); 
                
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
		$html["datefix"] ='<script>/*<!--*/ jQuery(function(){ '
				.'rui.require("rui.datefix",function(){'
				.'rui.Datefix.fix_dateselect("#'.$html["elm_id"].'"); });'
				.'}); /*-->*/</script>';
		
		// JS：日付の誤り訂正
		$html["japcal"] ='<script>/*<!--*/ jQuery(function(){ '
				//.'rui.require("rui.japcal",function(){'
				.'rui.Japcal.year_input_exchange("#'.$html["elm_id"].'");'
				//.'});'
				.'}); /*-->*/</script>';
				
		// JS：日付選択カレンダーポップUI
		$html["datepick"] .='<script>/*<!--*/ jQuery(function(){'
				.'rui.require("jquery.datepick",function(){'
				.'rui.Datepick.impl_dateselect('
				.'"#'.$html["elm_id"].'",{yearRange:"'.$y1.':'.$y2.'"}); });'
				.'}); /*-->*/</script>';
		
		$format =$params["format"]
				? $params["format"]
				: '{%l}{%yp}{%mp}{%dp}{%datefix}';/*{%datepick}*/
		$html["full"] =$html["head"].str_template_array($format,$html).$html["foot"];
		
		$this->html =$html["full"];
		$this->assign =$html;
	}
    
	function input_type_date_get_select ($name, $value, $list, $attrs, $postfix, $config=array()) {
	
		$html ="";
		$html .='<select name="'.$name.'"'.$attrs.'>';
		$html .='<option value=""></option>';
		
		foreach ($list as $v) {
		
            $label =$v;
            
            if ($postfix) { 
                
                $label =$v.$postfix;
                
            } elseif ($config["year_format"]=="wareki") {
                
                $label =get_japanese_year($v);
                
            } elseif ($config["year_format"]=="wareki_heiki") {
                
                $label =$v."(".get_japanese_year($v).")";
                
            } elseif ($config["year_format"]=="seireki_heiki") {
                
                $label =get_japanese_year($v)."(".$v.")";
            }
            
			$selected =(strlen($value) && (int)$v == (int)$value);
            $html .='<option value="'.$v.'"'
					.($selected ? ' selected="selected"' :'')
					.'>'.$label.'</option>';
		}
		
		$html .='</select>';
		
		return $html;
	}

	private function input_type_date_parse_range ($range) {
	
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

	private function input_type_date_parse_value ($value) {
		
		$today_pattern ='!^today'
				.'(?:([\+\-]\d+)y)?'
				.'(?:([\+\-]\d+)m)?'
				.'(?:([\+\-]\d+)d)?'
				.'(?:([\+\-]\d+)h)?'
				.'(?:([\+\-]\d+)i)?'
				.'(?:([\+\-]\d+)s)?$!';
				
		if (preg_match($today_pattern,$value,$match)) {
			
			$value =date("Y/m/d H:i:s",mktime(
				date("H")+$match[4],
				date("i")+$match[5],
				date("s")+$match[6],
				date("m")+$match[2],
				date("d")+$match[3],
				date("Y")+$match[1]
			));
		}
		
		return $value;
	}
}