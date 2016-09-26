<?php

use R\Lib\Core\String;

/**
 * NS対応版への移行機能
 */
class Transit
{
	/**
	 * 
	 */
	public static function installAutoload ()
	{
		// NS対応版の読み込み
		require_once(dirname(__FILE__).'/../../src/autoload.php');
	}

	/**
	 * 
	 */
	public static function loadModule ($type, $name)
	{
		// rule_*モジュールの読み込み
		if ($type == "rule") {

			$rule_class ='R\\Lib\\Form\\Rule\\'.String::camelize($name);

			if (class_exists($rule_class)) {

				return function ($value ,$option) use ($rule_class) {

					$rule =new $rule_class(array("option"=>$option));
					
					return $rule->check($value)
						? false
						: $rule->getMessage();
				};
			}
			
		// input_type_*モジュールの読み込み
		} else if ($type == "input_type") {

			$input_class ='R\\Lib\\Form\\Input\\'.String::camelize($name);
			
			if (class_exists($input_class)) {

				return function ($params, $preset_value, $postset_value, $smarty) use ($input_class) {

					$value =$postset_value;
					$params["value"] =$preset_value;
					
					$input =new $input_class($value,$params);
					
					if ($assignVars =$input->getAssign()) {
					
						$smarty->assign($assignVars);
					}

					return $input->getHtml();
				};
			}

			$input_func = "input_type_".$name;
			$input_file = __DIR__."/../../plugins/Smarty/input_type/".$input_func.".php";

			if (file_exists($input_file)) {

				require_once($input_file);
				return $input_func;
			}

		// search_type_*モジュールの読み込み
		} else if ($type == "search_type") {

			$search_class ='R\\Lib\\Query\\Search\\'.String::camelize($name);
			
			if (class_exists($search_class)) {

				return function ($name, $target, $input, $setting, $context) use ($search_class) {
					
					// 配列での入力であれば空の要素を削除
					if (is_array($input)) {

						foreach ($input as $k => $v) {
							
							if (strlen($v)===0) {
								
								unset($input[$k]);
							}
						}
					}

					// 入力がなければnull
					if ((is_array($input) && count($input===0)) || strlen($input)===0) {
						
						return null;
					}

					$setting["target"] =$target;

					$search =new $search_class($setting);

					return $search->getQuery($input);
				};
			}

		// その他のモジュールの読み込み
		} else  {

			$ns_map =array(
				"csvfilter" => 'CSVHandler\\Filter\\',
				"rdoc_entry" => 'Rapper\\RdocEntry\\',
				"readme" => 'Rapper\\Readme\\',
				//"webapp_skel" => 'Rapper\\WebappSkel\\',
			);

			$class ='R\\Plugin\\'.$ns_map[$type].String::camelize($name);
			$method = array($class, $type."_".$name);
			if ($class && class_exists($class) && is_callable($method)) {
				return $method;
			}
		}
	}


	/**
	 * 
	 */
	public static function loadRapper ($mod, $options)
	{
		$r =new R\Lib\Rapper\Rapper;
        $r->require_mod($mod);
        $r->apply_filters("init",$options);
        $r->apply_filters("proc",$options);
	}
}