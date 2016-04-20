<?php
	
	//-------------------------------------
	// Listインスタンスのファクトリ
	function get_list ($name, $controller=null) {
		
		$list_base =new List_App;
		
		if (is_array($name)) {
			
			$list_base->init(null,array(
				"options" =>$name,
			),$controller);
			
			return $list_base;
			
		} else {
		
			return $list_base->get_instance($name,$controller);
		}
	}
		
	//-------------------------------------
	// pageに対応するControllerのActionを実行
	function raise_action ($page, $options=array()) {
		
		list($controller_name, $action_name) =explode('.',$page,2);
		$controller_class_name =str_camelize($controller_name)."Controller";
		$action_method_name ="act_".$action_name;
		
		if ( ! class_exists($controller_class_name)) {
				
			return false;
		}
		
		$controller_obj =new $controller_class_name($controller_name,$action_name,$options);
		
		$controller_obj->before_act();
		
		if (is_callable(array($controller_obj,$action_method_name))) {
			
			$controller_obj->$action_method_name();
		}
		
		$controller_obj->after_act();
		
		return $controller_obj;
	}
