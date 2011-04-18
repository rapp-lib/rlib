<?php

//-------------------------------------
// 
class Model {
	
	protected static $instance =array();
	
	//-------------------------------------
	// インスタンスのファクトリ
	public static function load ($name=null) {
		
		$name =$name
				? $name."Model"
				: "Model_App";
		
		if ( ! self::$instance[$name]) {
			
			self::$instance[$name] =new $name;
		}
		
		return self::$instance[$name];
	}
}