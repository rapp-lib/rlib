<?php

	function smarty_modifier_options ($list_name, $param=null) {
		
		$list_options =obj("ListOptions")->get_instance($list_name);
		
		return $list_options->options($param);
	}
	