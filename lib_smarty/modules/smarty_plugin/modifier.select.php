<?php
	
	function smarty_modifier_select () {
		
		$args =func_get_args();
		$key =array_shift($args);
		$list_name =array_shift($args);
		$params =$args;
		
		$list_options =get_list($list_name);
		
		return $list_options->select($key,$params);	
	}
	