<?php


	//-------------------------------------
	//
	function array_extract ( & $arr) {
		
		foreach ($arr as $k => $_v) {
			
			if (is_array($arr[$k])) {
				
				array_extract($arr[$k]);
			}
			
			$ref =& ref_array($arr,$k);
			$ref =$arr[$k];
		}
	}
	
	//-------------------------------------
	//
	function & ref_array ( & $arrx, $path) {
	
		if (is_array($path)) {
			
			foreach ($path as $k => $v) {
				
				$path[$k] =str_replace(".","..",$path[$k]);
			}
			
			$path_str =implode('.',$path);
			
		} else {
			
			$path_str =$path;
			
			$path =str_replace("..",'__DELIM__',$path);
			$path =explode('.', $path);
			
			foreach ($path as $k => $v) {
				
				$path[$k] =str_replace("__DELIM__",".",$path[$k]);
			}
		}
		
		$_list =& $arrx;

		foreach ($path as $i => $key) {
			
			if (is_numeric($key) && intval($key) > 0 || $key === '0') {
				
				$key =intval($key);
			
			} elseif ($key == "[]") {
				
				$key =$_list
						? max(array_keys($_list))+1
						: 0;
			}
			
			if (count($path)-1 !== $i && ! isset($_list[$key])) {
			
				$_list[$key] =array();
			}
		
			$_list =& $_list[$key];
		}
		
		return $_list;
	}
	
	//-------------------------------------
	// 
	function & array_registry ( & $arr, $name=null ,$value=null, $escape=false) {
		
		// 全取得
		if ($name === null) {
			
			return $arr;
			
		// 配列指定（name）
		} elseif (is_array($name)) {
			
			foreach ($name as $a_name => $a_value) {
			
				array_registry($arr,$a_name,$a_value,$escape);
			}
			
			return $arr;
		}
		
		// 必須定義（!...）
		if ($must_def =preg_match('/^!(.+)$/',$name,$match)) {
			
			$name =$match[1];
		}
		
		// 参照解決
		if ($name === false) {
		
			$ref =& $arr;
			
		} elseif ($escape) {
			
			$ref =& $arr[$name];
			
		} else {
			
			$ref =& ref_array($arr, $name);
		}
		
		// 値の取得
		if ($value === null) {
			
			return $ref;
		
		// 必須エラー
		} elseif ($must_def && $ref === null) {
			
			report_error("Registry must-be defined.",array(
				"name" =>$name
			));
		
		// 消去（value=false）
		} elseif ($value === false) {
		
			if ($name === false) {
			
				$arr =array();
				
			} elseif ($escape) {
				
				unset($arr[$name]);
				
			} else {
				
				if (preg_match('!^(.*?[^\.])\.([^\.]+)$!',$name,$match)) {
				
					$ref =& ref_array($arr, $match[1]);
					unset($ref[$match[2]]);
					
				} else {
				
					unset($arr[$name]);
				}
			}
			
		// 配列指定（value）
		} elseif (is_array($value)) {
			
			if ( ! is_array($ref)) {
				
				$ref =array();
			}
			
			foreach ($value as $a_name => $a_value) {
			
				array_registry($ref,$a_name,$a_value,$escape);
			}
		
		// 値の設定
		} else {
		
			$ref =$value;	
		}
		
		return $ref;
	}

	//-------------------------------------
	// 配列をJSON文字列に変換する
	function array_to_json ($entry) {
		
		require_once("Services/JSON.php");
		
		$agent =new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		
		$json =$agent->encodeUnsafe($entry);
		
		return $json;
	}

	//-------------------------------------
	// JSON文字列を配列に変換する
	function json_to_array ($json) {
		
		require_once("Services/JSON.php");
		
		$agent =new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		
		$entry =$agent->decode($json);
		
		return $entry;
	}