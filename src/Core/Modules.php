<?php
/*
	2016/07/16
		core/modules.php内の全関数の移行完了
	
	ＭＥＭＯ
	register_module内のref_globals
		registered_moduleとなっている
 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
use R\Lib\Core\String;

/**
 * 
 */
class Modules {

	/**
	 * [add_include_path description] 
	 * @param  [type] $file_path [description]
	 * @return [type]      [description]
	 */
	//include_pathを追加する
	public static function addIncludePath ($file_path) {
		
		$include_pathes =explode(PATH_SEPARATOR,get_include_path());
		
		if ( ! in_array($file_path,$include_pathes)) {
			
			ini_set("include_path",ini_get("include_path")
					.PATH_SEPARATOR.$file_path);
		}
	}
	
	/**
	 * [find_include_path description] 
	 * @param  [type] $file_name [description]
	 * @return [type]      [description]
	 */
	// ファイル探索
	public static function findIncludePath ($file_name) {
		
		if (file_exists($file_name)) {
			
			return realpath($file_name);
		}
				
		$include_pathes =explode(PATH_SEPARATOR,get_include_path());
		
		foreach ($include_pathes as $include_path) {
			
			$find_path =$include_path."/".$file_name;
		
			if (file_exists($find_path)) {
				
				return realpath($find_path);
			}
		}
		
		return null;
	}
	
	/**
	 * [load_module description] 
	 * @param  [type] $module_group [description]
	 * @param  [type] $module_id [description]
	 * @param  [type] $force [description]
	 * @return [type]      [description]
	 */
	// モジュール読み込み
	public static function loadModule ($module_group, $module_id, $force=false) {
		
		// Closureかメソッドがわたっていればそのまま返す
		if (( ! is_string($module_id) && is_callable($module_id))
				|| (is_array($module_id) && function_exists($module_id))) {
			
			return $module_id;
		}
		
		// 登録済みmoduleから読み込み
		$modules =& Vars::refGlobals("registered_module");
		$func =$modules[$module_group][$module_id];
		
		if ($func) {
			
			return $func;
		}
		
		// modules以下のファイルから読み込み
		$dir ="modules/".$module_group."/";
		$func_name =$module_group."_".$module_id;
		
		$func =Modules::loadFunction($func_name,$dir);
		
		if ($func) {
			
			return $func;
		}

		// [Transit] NS対応版から読み込む
		if ($func =Transit::loadModule($module_group, $module_id)) {
			
			return $func;
		}
		
		if ($force) {
		
			Report::reportError("Module is not found",array(
				"module_group" =>$module_group,
				"module_id" =>$module_id,
				"dir" =>$dir,
				"func_name" =>$func_name,
			));
		}
		
		return null;
	}
	
	/**
	 * [register_module description] 
	 * @param  [type] $module_group [description]
	 * @param  [type] $module_id [description]
	 * @param  [type] $module [description]
	 */
	// モジュール登録
	public static function registerModule ($module_group, $module_id, $module) {
		
		$modules =& Vars::refGlobals("registered_module");
		$modules[$module_group][$module_id] =$module;
	}
	
	/**
	 * [load_function description] 
	 * @param  [type] $func_name [description]
	 * @param  [type] $dir [description]
	 * @param  [type] $force [description]
	 * @return [type]      [description]
	 */
	// 関数ローダ
	public static function loadFunction ($func_name, $dir="", $force=false) {
		
		if (function_exists($func_name)) {
			
			return $func_name;
		}
		
		$path =Modules::findIncludePath($dir."/".$func_name.".php");
		
		if ($path && (include($path)) && function_exists($func_name)) {
			
			return $func_name;
		}
		
		$path =Modules::findIncludePath($dir."/".$func_name.".function.php");
		
		if ($path && (include($path)) && function_exists($func_name)) {
			
			return $func_name;
		}
		
		if ($force) {
		
			Report::reportError("Function is not found",array(
				"func_name" =>$func_name,
				"dir" =>$dir,
			));
		}
		
		return null;
	}
	
	/**
	 * [load_class description] 
	 * @param  [type] $class_name [description]
	 * @return [type]      [description]
	 */
	// 標準クラスローダ
	public static function loadClass ($class_name) {
	
		$file_name =$class_name.".class.php";
		
		if (Modules::findIncludePath($file_name)) {
			
			require_once($file_name);
			return $class_name;
		}
		
		if (Modules::findIncludePath("default/".$file_name)) {
			
			require_once("default/".$file_name);
			return $class_name;
		}

		$dirs =str_replace('_',' / ',$class_name);
		$dirs =String::underscore($dirs);
		$dirs =str_replace(' ','',$dirs);
		$dirs =explode("/",$dirs);
		$path ="";

		foreach ($dirs as $dir) {

			$path .=$dir."/";

			if ($file =Modules::findIncludePath($path.$class_name.".class.php")) {

				require_once($file);
				return $class;
			}
		}
		
		return null;
	}
	
	/**
	 * [load_lib description] 
	 * @param  [type] $lib_name [description]
	 * @return [type]      [description]
	 */
	public static function loadLib ($lib_name) {
		
		if (check_loaded("lib",$lib_name,null)) {
			
			return;
		}

		$lib_dir =Vars::registry("Path.lib_dir")."/".$lib_name;
		Modules::addIncludePath($lib_dir);
		
		if (file_exists($lib_dir."/".$lib_name.".php")) {
		
			require_once($lib_dir."/".$lib_name.".php");
		}
		
		Modules::checkLoaded("lib",$lib_name,true);
	}
	
	/**
	 * [check_loaded description] 
	 * @param  [type] $type [description]
	 * @param  [type] $path [description]
	 * @param  [type] $flg [description]
	 * @param  [type] $report_error [description]
	 * @return [type]      [description]
	 */
	function checkLoaded ($type, $path, $flg=null, $report_error=false) {
		
		$check =& Vars::refGlobals("check_loaded");
		
		if ( ! isset($check[$type][$path])) {
			
			$check[$type][$path] =false;
		}
		
		if ($flg === null) {
			
			if ($report_error && $check[$type][$path]) {
				
				Report::reportError("check_loaded error",array(
					"type" =>$type,
					"path" =>$path,
				));
			}
			
		} else {
		
			$check[$type][$path] =(boolean)$flg;
		}
		
		return $check[$type][$path];
	}
	
	/**
	 * [obj description] 
	 * @param  [type] $class_name [description]
	 * @return [type]      [description]
	 */
	// 再利用可能なインスタンスの生成
	function obj ($class_name) {
		
		static $cache =array();
		
		if ( ! isset($cache[$class_name])) {
			
			$cache[$class_name] =new $class_name;
		}
		
		return $cache[$class_name];
	}
}
