<?php
	
	//-------------------------------------
	// Listインスタンスのファクトリ
	function get_list ($name) {
		
		$instance =& ref_globals("loaded_list_option");
		
		if ( ! $instance[$name]) {
		
			$class_name =str_camelize($name)."List";
			$config =registry("List.".$name);
				
			$instance[$name] =new $class_name;
			$instance[$name]->init($name,$config);
		}
		
		return $instance[$name];
	}
	