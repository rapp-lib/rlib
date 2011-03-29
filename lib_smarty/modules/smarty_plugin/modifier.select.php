<?php

	function smarty_modifier_select ($key, $list_name, $param=null) {
		
		$list_options =obj("ListOptions")->get_instance($list_name);
		
		return $list_options->select($key,$param);
	}
	