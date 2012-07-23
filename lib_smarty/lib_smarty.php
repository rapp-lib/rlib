<?php
	
	//-------------------------------------
	// Listインスタンスのファクトリ
	function get_list ($name) {
		
		$list_base =new List_App;
		
		if (is_array($name)) {
			
			$list_base->init(null,array(
				"options" =>$name,
			));
			return $list_base;
			
		} else {
		
			return $list_base->get_instance($name);
		}
	}
	