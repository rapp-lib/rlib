<?php

//-------------------------------------
// DB接続
class DBI {

	protected static $instance =array();
	protected static $default_connection ="default";
	
	//-------------------------------------
	// インスタンスの読み込み
	public static function load ($name=null, $class=null) {
		
		if ( ! $name) {
			
			$name =self::$default_connection;
		}
		
		if ($class === null) {
			
			$class ="DBI_App";
		}
		
		if ( ! self::$instance[$name]) {
		
			if (($connect_info =registry("DBI.connection.".$name))
					|| ($connect_info =registry("DBI.preconnect.".$name))) {
				
				$class =$connect_info["class"]
						? $connect_info["class"]
						: $class;
				self::$instance[$name] =new $class($name);
				self::$instance[$name]->connect($connect_info);
			
			} else {
			
				self::$instance[$name] =new $class($name);
			}
		}
		
		return self::$instance[$name];
	}
	
	//-------------------------------------
	// デフォルトで読み込まれるインスタンスの切り替え
	public static function change ($name=null) {
		
		if ($name !== null) {
		
			self::$default_connection =$name;
		}
		
		return self::$default_connection;
	}
}