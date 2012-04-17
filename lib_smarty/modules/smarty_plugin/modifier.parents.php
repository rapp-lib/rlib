<?php

	function smarty_modifier_parents () {
		
		$args =func_get_args();
		$list_name =array_shift($args);
		$params =$args;
		
		$list_options =get_list($list_name);
		
		return $list_options->parents($params);
	}
	