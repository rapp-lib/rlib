<?php

	function smarty_resource_module_source ($tpl_name, &$tpl_source, &$smarty) {
		
		$file =$smarty->resolve_resource_module($tpl_name,true);
		$tpl_source =file_get_contents($file);
		return true;
	}
	
	function smarty_resource_module_timestamp ($tpl_name, &$tpl_timestamp, &$smarty) {
	
		$file =$smarty->resolve_resource_module($tpl_name);
		$tpl_timestamp =filemtime($file);
		return true;
	}
	
	function smarty_resource_module_secure ($tpl_name, &$smarty) {
		
		return true;
	}
	
	function smarty_resource_module_trusted ($tpl_name, &$smarty) {
		
		return true;
	}