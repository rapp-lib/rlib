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
			$value =& ref_array($_REQUEST,$name_ref);
			
			// 日付指定
			if ($request["mode"] == "date") {
			
				$date_is_set =($value["y"] || $value["m"]);
				$date_str =(int)$value["y"].'/'. (int)$value["m"]."/".(int)$value["d"];
				
				$time_is_set =(strlen($value["h"]) || strlen($value["i"]));
				$time_str =(int)$value["h"].':'. (int)$value["i"];
				
				$time_str .=$time_is_set && $value["s"]
						? ":".(int)$value["s"]
						: "";
				
				// Datetime
				if ($date_is_set && $time_is_set) {
					
					$value =$date_str." ".$time_str;
				
				// Date
				} elseif ($date_is_set) {
					
					$value =$date_str;
				
				// Time
				} elseif ($time_is_set) {
					
					$value =$time_str;	
				
				// 不正値
				} else {
				
					$value =false;
				}
			
			// ファイル指定
			} elseif ($request["mode"] == "file") {

				$result =obj("UserFileManager")->save_file(array(
					"is_uploaded_resource" =>true,
					"group" =>$request["group"],
					"src_filename" =>$_FILES[$request["files_key"]]["tmp_name"], 
					"src_filename_alias" =>$_FILES[$request["files_key"]]["name"], 
				));
				
				if ($result["status"] == "success") {

					$value =$result["code"];

				} elseif ($result["status"] == "denied") {

					report_warning($result["message"],array(
						"request" =>$request,
						"resource" =>$resource,
					));

				} elseif ($result["status"] == "error") {

					report_error($result["message"],array(
						"request" =>$request,
						"resource" =>$resource,
					));
				}

			// layout指定
			} elseif ($layout =$request["layout"]) {
			
				$value =str_template_array($layout,$value);
			}
		}
	}
}