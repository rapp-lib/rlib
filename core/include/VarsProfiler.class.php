<?php

/**
 * 変数の解析を行う
 */
class VarsProfiler {

	/**
	 * 値を解析する
	 */
	public static function profile ($value) {

		$info =array();

		if ($info =self::profile_function($value)) {
		
		} else if ($info =self::profile_class($value)) {

		} else if (is_array($value)) {

			$info["type"] ="array(".count($value).")";
			$info["value"] =array();
			
			foreach ($value as $k => $v) {

				$info["value"][$k] =Report::profile($v);
			}

		} else if (is_bool($value)) {

			$info["type"] =$value ? "true" : "false";

		} else if (is_null($value)) {

			$info["type"] ="null";

		} else if (is_string($value)) {

			$info["type"] ="string(".strlen($value).")";
			$info["value"] ='"'.$value.'"';

		} else {

			$info["type"] =gettype($value);
			$info["value"] =(string)$value;
		}

		return $info;
	}

	/**
	 * 関数/メソッドの情報を解析する
	 */
	public static function profile_function ($func) {

		$info =array();
		$ref =null;
		
		if ( ! is_callable($func)) {

			return array();
		}

		if (is_array($func) && method_exists($func[0], $func[1])) {

			$ref =new ReflectionMethod($func[0], $func[1]);
			$class_name =$ref->getDeclaringClass()->getName();

		} else {
			
			$ref =new ReflectionFunction($func);
		}

		$info["name"] =$ref->getName();
		$info["file"] =$ref->getFileName();
		$info["line"] =$ref->getStartLine();
		$info["ns"] =$ref->getNamespaceName();
		$info["comment"] =$ref->getDocComment();
		$info["file_short"] =self::to_short_filename($info["file"]);
		
		$info["params"] =array();

		foreach ($ref->getParameters() as $ref_param) {

			$info["params"][] =array(
				"type" =>$ref_param->getType()->__toString(),
				"name" =>$ref_param->getName(),
				"passed_by_reference" =>$ref_param->isPassedByReference(),
				"default_value" =>$ref_param->getDefaultValue(),
				"default_value_const" =>$ref_param->getDefaultValueConstantName(),
			);
		}

		return $info;
	}

	/**
	 * クラス/オブジェクトの情報を解析する
	 */
	public static function profile_class ($class) {

		$info =array();
		$ref =null;
		
		if (is_object($class)) {

			$ref =new ReflectionObject($class);
		
		} else if (class_exists($class)) {
			
			$ref =new ReflectionClass($class);
		
		} else {

			return array();
		}

		$info["name"] =$ref->getName();
		$info["file"] =$ref->getFileName();
		$info["line"] =$ref->getStartLine();
		$info["ns"] =$ref->getNamespaceName();
		$info["comment"] =$ref->getDocComment();
		$info["file_short"] =self::to_short_filename($info["file"]);

		$info["value"] =array();
		
		foreach ($value as $k => $v) {

			$info["value"][$k] =Report::profile($v);
		}

		return $info;
	}

	/**
	 * ファイル名を省略名に変換する
	 */
	public static function to_short_filename ($file) {

		return basename($file);
	}
}