<?php
	
	//-------------------------------------
	// include_pathを追加する
	function add_include_path ($file_path) {
	
		$include_pathes =explode(PATH_SEPARATOR,get_include_path());
		
		if ( ! in_array($file_path,$include_pathes)) {
			
			ini_set("include_path",ini_get("include_path")
					.PATH_SEPARATOR.$file_path);
		}
	}
	
	//-------------------------------------
	// ファイル探索
	function find_include_path ($file_name) {
		
		if (file_exists($file_name)) {
			
			return realpath($file_name);
		}
				
		$include_pathes =explode(PATH_SEPARATOR,get_include_path());
		
		if (is_resource($fp =@fopen($file_name,"r",true)) && fclose($fp)) {
		
			foreach ($include_pathes as $include_path) {
				
				$find_path =$include_path."/".$file_name;
			
				if (file_exists($find_path)) {
					
					return realpath($find_path);
				}
			}
		}
		
		return null;
	}
	
	//-------------------------------------
	// モジュール読み込み
	function load_module ($module_group, $module_id, $force=false) {
		
		// メソッドがわたっていればそのまま返す
		if (is_array($module_id) && function_exists($module_id)) {
			
			return $module_id;
		}
		
		// 登録済みmoduleから読み込み
		$modules =& ref_globals("registered_module");
		$func =$modules[$module_group][$module_id];
		
		if ($func) {
			
			return $func;
		}
		
		// modules以下のファイルから読み込み
		$dir ="modules/".$module_group."/";
		$func_name =$module_group."_".$module_id;
		
		$func =load_function($func_name,$dir);
		
		if ($func) {
			
			return $func;
		}
		
		if ($force) {
		
			report_error("Module is not found",array(
				"module_group" =>$module_group,
				"module_id" =>$module_id,
				"dir" =>$dir,
				"func_name" =>$func_name,
			));
		}
		
		return null;
	}
	
	//-------------------------------------
	// モジュール登録
	function register_module ($module_group, $module_id, $module) {
		
		$modules =& ref_globals("registered_module");
		$modules[$module_group][$module_id] =$module;
	}
	
	//-------------------------------------
	// 関数ローダ
	function load_function ($func_name, $dir="", $force=false) {
		
		if (function_exists($func_name)) {
			
			return $func_name;
		}
		
		$path =find_include_path($dir."/".$func_name.".php");
		
		if ($path && (include($path)) && function_exists($func_name)) {
			
			return $func_name;
		}
		
		$path =find_include_path($dir."/".$func_name.".function.php");
		
		if ($path && (include($path)) && function_exists($func_name)) {
			
			return $func_name;
		}
		
		if ($force) {
		
			report_error("Function is not found",array(
				"func_name" =>$func_name,
				"dir" =>$dir,
			));
		}
		
		return null;
	}
	
	//-------------------------------------
	// 標準クラスローダ
	function load_class ($class_name) {
	
		$file_name =$class_name.".class.php";
		
		if (find_include_path($file_name)) {
			
			require_once($file_name);
			return $class_name;
		}
		
		if (find_include_path("default/".$file_name)) {
			
			require_once("default/".$file_name);
			return $class_name;
		}
		
		return null;
	}
	
	//-------------------------------------
	//
	function load_lib ($lib_name) {
		
		if (check_loaded("lib",$lib_name,null)) {
			
			return;
		}

		$lib_dir =registry("Path.lib_dir")."/".$lib_name;
		add_include_path($lib_dir);
		
		if (file_exists($lib_dir."/".$lib_name.".php")) {
		
			require_once($lib_dir."/".$lib_name.".php");
		}
		
		check_loaded("lib",$lib_name,true);
	}
	
	//-------------------------------------
	//
	function check_loaded ($type, $path, $flg=null, $report_error=false) {
		
		$check =& ref_globals("check_loaded");
		
		if ( ! isset($check[$type][$path])) {
			
			$check[$type][$path] =false;
		}
		
		if ($flg === null) {
			
			if ($report_error && $check[$type][$path]) {
				
				report_error("check_loaded error",array(
					"type" =>$type,
					"path" =>$path,
				));
			}
			
		} else {
		
			$check[$type][$path] =(boolean)$flg;
		}
		
		return $check[$type][$path];
	}
	
	//-------------------------------------
	// 再利用可能なインスタンスの生成
	function obj ($class_name) {
		
		static $cache =array();
		
		if ( ! isset($cache[$class_name])) {
			
			$cache[$class_name] =new $class_name;
		}
		
		return $cache[$class_name];
	}