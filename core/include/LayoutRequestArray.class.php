<?php

//-------------------------------------
// 
class LayoutRequestArray {
	
	//-------------------------------------
	// 
	public function fetch_request_array () {
		
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
			
				$date_str ="";
				
				if ($values["y"] || $values["m"] || $values["d"]) {
					
					$date_str .=(int)$values["y"].'/'. (int)$values["m"]."/".(int)$values["d"];
				}
				
				if ($date_str) {
				
					$date_str .=' ';
				}
				
				if ($values["h"] || $values["i"]) {
				
					$date_str .=(int)$values["h"].':'. (int)$values["i"];
				
					if ($values["s"]) {
						
						$date_str .=":".(int)$values["s"];
					}
				}
				
				$values =$date_str;
				
			// layout指定
			} elseif ($layout =$request["layout"]) {
			
				$values =str_template_array($layout,$values);
			}
		}
	}
}