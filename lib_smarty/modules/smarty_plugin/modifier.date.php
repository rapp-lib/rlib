<?php

	function smarty_modifier_date ($string ,$format="Y/m/d" ) {
		
		if (is_numeric($string)) {
			
			$string =date("Y/m/d H:i:s",$string);
		}
		
		if ($longdate =longdate($string)) {
			
			$longdate["Y"] =$longdate["y"];
			$longdate["y"] =sprintf("%02d", $longdate["y"]%100);
			$longdate["n"] =(int)($longdate["m"]);
			$longdate["j"] =(int)($longdate["d"]);
			$longdate["H"] =$longdate["h"];
			unset($longdate["h"]);
			$longdate["M"] =get_date_m($longdate["m"]);
			
			$str ="";
			
			foreach ($longdate as $k => $v) {
				
				$format =str_replace($k,$v,$format);
			}
			
			return $format;
		
		} else {
		
			return "";
		}
	}
	
	function get_date_m ($month) {
		
		$month_list =array(
			"1" =>"Jan",
			"2" =>"Feb",
			"3" =>"Mar",
			"4" =>"Apr",
			"5" =>"May",
			"6" =>"Jun",
			"7" =>"Jul",
			"8" =>"Aug",
			"9" =>"Sep",
			"10" =>"Oct",
			"11" =>"Nov",
			"12" =>"Dec",
		);
		
		return $month_list[(int)$month];
	}
	