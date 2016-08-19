<?php
/*
	2016/07/16
		core/date.php内の全関数の移行完了
	
 */
namespace R\Lib\Core;


/**
 * 
 */
class Date {

	/**
	 * [longdate description] 
	 * @param  [type] $date_string [description]
	 * @return [type]      [description]
	 */
	// 範囲の広い日付
	public static function longdate ($date_string) {
		
		$date_pattern ='!^((\d*)[-/](\d*)[-/](\d*))?\D*((\d+):(\d+)(:(\d+))?)?!';
		$longdate =array();
		
		if (strlen($date_string) 
				&& preg_match($date_pattern,$date_string, $match)) {
			
			// Mysql例外日付
			if ($match[2]==="0000" || $match[3]==="00" || $match[4]==="00") {
				
				return array();
			}
			
			$longdate =array();
			$longdate["T"] =($match[1]?"date":"").($match[5]?"time":"");
			
			if ($match[1]) {
				$longdate["y"] =sprintf('%04d',$match[2]);
				$longdate["m"] =sprintf('%02d',$match[3]);
				$longdate["d"] =sprintf('%02d',$match[4]);
			}
			if ($match[5]) {
				$longdate["h"] =sprintf('%02d',$match[6]);
				$longdate["i"] =sprintf('%02d',$match[7]);
				$longdate["s"] =sprintf('%02d',$match[9]);
			}
		} else  {
			
			return array();
		}
		
		// 日付に関わる変換
		if ($longdate["T"] == "date" || $longdate["T"] == "datetime") {
		
			// ２ケタ表記の年の解決
			if ($longdate["y"] < 50) {
			
				$longdate["y"] +=2000;
				
			} elseif ($longdate["y"] < 100) {
			
				$longdate["y"] +=1900;
			}
			
			// 和暦（X）
			$longdate["X"] =Date::getJapaneseYear(
					$longdate["y"],$longdate["m"],$longdate["d"],"X");
			
			// X00表記の和暦（x）
			$longdate["x"] =Date::getJapaneseYear(
					$longdate["y"],$longdate["m"],$longdate["d"],"x");
			
			// 0-6表記の曜日計算（w）
			$longdate["w"] =Date::getWeekday($longdate["y"],$longdate["m"],$longdate["d"],"w");
			
			// 日本語短縮表記の曜日（W）
			$longdate["W"] =Date::getWeekday($longdate["y"],$longdate["m"],$longdate["d"],"W");
			
			// date関数標準表記に変換
			$longdate["Y"] =$longdate["y"];
			$longdate["y"] =sprintf("%02d", $longdate["y"]%100);
			$longdate["M"] =Date::getDateM($longdate["m"]);
			$longdate["n"] =(int)($longdate["m"]);
			$longdate["j"] =(int)($longdate["d"]);
		}
		
		// 時刻に関わる変換
		if ($longdate["T"] == "time" || $longdate["T"] == "datetime") {
		
			$longdate["H"] =$longdate["h"];
			$longdate["h"] =(int)($longdate["h"]);
		}
		
		return $longdate;
	}

	/**
	 * [longdate_format description] 
	 * @param  [type] $date_string [description]
	 * @param  [type] $format [description]
	 * @return [type]      [description]
	 */
	// longdateにおけるdate関数同等機能
	public static function longdateFormat ($date_string=null, $format="Y/m/d") {
		
		if ($date_string == null) {
			
			$date_string =time();
		}
		
		if (is_numeric($date_string)) {
		
			$date_string =date("Y/m/d H:i:s",$date_string);
		}
		
		$longdate =Date::longdate($date_string);
		
		if ( ! $longdate) {
		
			return "";
		}
		
		return preg_replace(
				'!('.implode('|',array_keys($longdate)).')!e',
				'$longdate["$1"]',
				$format);
	}
	
	/**
	 * [get_date_m description] 
	 * @param  [type] $month [description]
	 * @return [type]      [description]
	 */
	// 月名の計算
	public static function getDateM ($month) {
		
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
	
	/**
	 * [get_weekday description] 
	 * @param  [type] $year [description]
	 * @param  [type] $month [description]
	 * @param  [type] $mday [description]
	 * @param  [type] $mode [description]
	 * @return [type]      [description]
	 */
	// 曜日を求める
	public static function getWeekday ($year, $month, $mday, $mode="w") {
		
		if($month == 1 || $month == 2) {
			
			$year--;
			$month += 12;
		}
		
		$longdate["w"] =($year + intval($year/4) 
				- intval($year/100) 
				+ intval($year/400) 
				+ intval((13*$month+8)/5) + $mday) % 7;
		
		$wj_list =array("日","月","火","水","木","金","土");
		$longdate["W"] =$wj_list[$longdate["w"]];
		
		return $longdate[$mode];
	}

	/**
	 * [get_japanese_year description] 
	 * @param  [type] $y [description]
	 * @param  [type] $m [description]
	 * @param  [type] $d [description]
	 * @param  [type] $format [description]
	 * @return [type]      [description]
	 */
	// 和暦の計算
	public static function getJapaneseYear ($y, $m=12, $d=31, $format="X") {
		
		/*
			1868年09月07日以前は西暦表記とします
			1912年07月29日以前は「明治」- 1867
			1926年12月24日までは「大正」- 1911
			1989年01月07日までは「昭和」- 1925
			1989年01月08日以降は「平成」- 1988
		*/
		$fulldate =(int)sprintf('%04d%02d%02d',$y ,$m ,$d);
		$longdate =array();	
		
		if ($fulldate < 18680908) {
			
			$x_year =$y;
			$longdate["x"] =$x_year;
			$longdate["X"] =$x_year."年";
			
		} elseif ($fulldate <= 19120729) {
		
			$x_year =$y-1867;
			$longdate["x"] ='M'.sprintf('%02d',$x_year);
			$longdate["X"] ='明治'.($x_year > 1 ? $x_year : "元")."年";
			
		} elseif ($fulldate <= 19261224) {
		
			$x_year =$y-1911;
			$longdate["x"] ='T'.sprintf('%02d',$x_year);
			$longdate["X"] ='大正'.($x_year > 1 ? $x_year : "元")."年";
			
		} elseif ($fulldate <= 19890107) {
		
			$x_year =$y-1925;
			$longdate["x"] ='S'.sprintf('%02d',$x_year);
			$longdate["X"] ='昭和'.($x_year > 1 ? $x_year : "元")."年";
			
		} else {
		
			$x_year =$y-1988;
			$longdate["x"] ='H'.sprintf('%02d',$x_year);
			$longdate["X"] ='平成'.($x_year > 1 ? $x_year : "元")."年";
		}
		
		return $longdate[$format];
	}
}