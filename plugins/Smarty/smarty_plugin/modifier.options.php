<?php

	function smarty_modifier_options () {
		
		$args =func_get_args();
		$list_name =array_shift($args);
		$params =$args;
		
		$list_options =get_list($list_name);
		
		return $list_options->options($params);
	}
	