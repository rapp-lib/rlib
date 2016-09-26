<?php

//-------------------------------------
// カレンダー作成を行う
class CalendarFactory {
	
	//-------------------------------------
	// カレンダー情報の構築
	public function build_calendar ($current, $options=array()) {
		
		// current=日付文字列
		// options.start_by_monday=1であれば月曜から始まる
		
		if ( ! $current) {
		 	
			$current =date("Y/m/d");
		}
		
		$current_ld =longdate($current);
		
		$y =$current_ld["Y"];
		$m =$current_ld["m"];
		$d =$current_ld["d"];
		
		$today_timestamp =strtotime(date("Y/m/d"));
		
		$dates =array();
		
		$calendar_day_list =$this->get_calendar_day_list($y,$m,$options["start_by_monday"]);
		
		foreach ($calendar_day_list as $wy => $days) {
			
			foreach ($days as $wx => $day) {
				
				$w =$options["start_by_monday"] 
						? ($wx+1)%7 
						: $wx;
				
				$dates[$wy][$wx] =array(
					"date" =>0,
					"d" =>0,
					"timestamp" =>0,
					"w" =>$w,
					"is_sunday" =>$w==0,
					"is_saturday" =>$w==6,
					"is_extra" =>false,
					"is_past" =>false,
					"is_future" =>false,
				);
				
				if ($day < 0) {
					
					$day =-$day;
					$dates[$wy][$wx]["is_extra"] =true;
					
					$timestamp =$day < 15
							? mktime(0,0,0,$m+1,$day,$y)
							: mktime(0,0,0,$m-1,$day,$y);
				} else {
					
					$last_day_of_month =$day;
					
					$timestamp =mktime(0,0,0,$m,$day,$y);
				}
				
				if ($today_timestamp > $timestamp) {
				
					$dates[$wy][$wx]["is_past"] =true;
					
				} elseif ($today_timestamp+24*60*60 <= $timestamp) {
				
					$dates[$wy][$wx]["is_future"] =true;
				}
				
				$dates[$wy][$wx]["timestamp"] =$timestamp;
				$dates[$wy][$wx]["date"] =date("Y-m-d",$timestamp);
				$dates[$wy][$wx]["d"] =$day;
			}
		}
		
		$calendar =array(
				"dates" =>$dates,
				"last_day_of_month" =>$last_day_of_month,
				"next_month" =>($m==12 ? $y+1 : $y)."-".($m==12 ? 1 : $m+1)."-1",
				"prev_month" =>($m==1  ? $y-1 : $y)."-".($m==1  ? 12 :$m-1)."-1",
				"current" =>$current,
				"today" =>date("Y-m-d"));
				
		return $calendar;
	}

	//-------------------------------------
	// 
	protected function get_calendar_day_list ($y,$m,$start_by_monday=false) {
	
		$fdayw =date("w",mktime(0,0,0,$m,1,$y));
		$lday =date("d",mktime(0,0,0,$m+1,0,$y));
		$week =array();
		$day =($start_by_monday ? 2 : 1)-$fdayw;
		$day =$day > 1 ? $day-7 : $day;
		
		for ($y=0; $y<6; $y++) {
		
			$week[$y] =array();
			
			for ($x=0; $x<7; $x++) {
				
				if ($day < 1 || $lday < $day) { 
					$week[$y][$x] =-date("d",mktime(0,0,0,$m,$day,$y));
				} else {
					$week[$y][$x] =$day;
				}
				
				$day++;
			}
			
			if ($day > $lday) {
				
				break;
			}
		}
		
		return $week;
	}
}