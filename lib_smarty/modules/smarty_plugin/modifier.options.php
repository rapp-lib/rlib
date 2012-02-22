<?php

	function smarty_modifier_options ($list_name, $param=null) {
		
		$args =func_get_args();
		$list_name =array_shift($args);
		$params =$args;
		
		$list_options =get_list($list_name);
		
		return $list_options->options($params);
	}
	