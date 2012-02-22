<?php

//-------------------------------------
// 
class LayoutRequestArray {
	
	//-------------------------------------
	// 
	public function fetch_request_array () {
		
		if ( ! isset($_REQUEST["_LRA"])) {
			
			return;
		}
		
		$requests =$_REQUEST["_LRA"];
			
		foreach ((array)$requests as $request_index => $request) {
			
			$name =$request["name"];
			$var_name =$request["var_name"]
					? $request["var_name"]
					: $name;
					
			$name_ref =$var_name;
			$name_ref =str_replace('.','..',$name_ref);
			$name_ref =str_replace('][','.',$name_ref);
			$name_ref =str_replace('[','.',$name_ref);
			$name_ref =str_replace(']','',$name_ref);
			$values =& ref_array($_REQUEST,$name_ref);
			
			// 日付指定
			if ($request["mode"] == "date") {
			
				$date_is_set =($values["y"] || $values["m"] || $values["d"]);
				$date_str =(int)$values["y"].'/'. (int)$values["m"]."/".(int)$values["d"];
				
				$time_is_set =(strlen($values["h"]) || strlen($values["i"]));
				$time_str =(int)$values["h"].':'. (int)$values["i"];
				
				$time_str .=$time_is_set && $values["s"]
						? ":".(int)$values["s"]
						: "";
				
				// Datetime
				if ($date_is_set && $time_is_set) {
					
					$values =$date_str." ".$time_str;
				
				// Date
				} elseif ($date_is_set) {
					
					$values =$date_str;
				
				// Time
				} elseif ($time_is_set) {
					
					$values =$time_str;	
				
				// 不正値
				} else {
				
					$values =null;
				}
				
			// layout指定
			} elseif ($layout =$request["layout"]) {
			
				$values =str_template_array($layout,$values);
			}
		}
	}
}