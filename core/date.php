<?php

	//-------------------------------------
	// 範囲の広い日付
	function longdate ($date_string) {
		
		if (strlen($date_string) &&
				preg_match('!^((\d*)[-/](\d*)[-/](\d*))?'.
				'\D*((\d+):(\d+)(:(\d+)?))?!',
				$date_string, $match)) {
			
			$longdate =array(
				"y" =>sprintf('%04d',$match[2]),
				"m" =>sprintf('%02d',$match[3]),
				"d" =>sprintf('%02d',$match[4]),
				"h" =>sprintf('%02d',$match[6]),
				"i" =>sprintf('%02d',$match[7]),
				"s" =>sprintf('%02d',$match[9])
			);
			
			if ($longdate["y"] < 50) {
			
				$longdate["y"] +=2000;
				
			} elseif ($longdate["y"] < 100) {
			
				$longdate["y"] +=1900;
			}
			
			$h =floor($longdate["y"]/100);
			$y =$longdate["y"]%100;
			$m =$longdate["m"];
			$d =$longdate["d"];
			$w =abs($y+floor($y/4)+floor($h/4)-2*$h+floor(13*($m+1)/5)+$d)%7;
			$longdate["w"] =abs($w-1)%7;
			
			$wj_list =array("日","月","火","水","木","金","土");
			$longdate["W"] =$wj_list[$longdate["w"]];
			
			return $longdate;
		}
		
		return array();
	}