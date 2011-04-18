<?php

//-------------------------------------
// 選択肢表現クラス
class ListOptions {
	
	protected $name;
	protected $config;
	
	//-------------------------------------
	// コンストラクタ
	public function get_instance ($name) {
		
		$cache =& ref_globals("list_option_class");
		
		if ( ! $cache[$name]) {
			
			$class_name =str_camelize($name)."List";
			$config =registry("List.".$name);
				
			$cache[$name] =class_exists($class_name)
					? new $class_name
					: new ListOptions;
			
			$cache[$name]->init($name,$config);
		}
		
		return $cache[$name];
	}
	
	//-------------------------------------
	// 初期化
	public function init ($name,$config) {
		
		$this->name =$name;
		$this->config =$config;
	}
	
	//-------------------------------------
	// オプション取得
	public function options ($param=array()) { 
		
		if ( ! is_array($this->config["options"])) {
			
			report_error("List-options is not defined.",array(
				"name" =>$this->name,
				"config" =>$this->config,
			));
		}
		
		return (array)$this->config["options"]; 
	}
	
	//-------------------------------------
	// オプション選択
	public function select ($key=null) {
	
		$options =$this->options();
		
		return $options[$key];
	}
	
	//-------------------------------------
	// 親要素との対応取得
	public function parents ($param=array()) { 
		
		if ( ! is_array($this->config["parents"])) {
			
			report_error("List-parents is not defined.",array(
				"name" =>$this->name,
				"config" =>$this->config,
			));
		}
		
		return (array)$this->config["options"]; 
	}
}