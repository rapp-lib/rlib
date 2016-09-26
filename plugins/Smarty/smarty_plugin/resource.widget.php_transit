<?php

	function smarty_resource_widget_source ($tpl_name, $tpl_source, $smarty) {
		
		$file =$smarty->resolve_resource_widget($tpl_name,true);
		$tpl_source =file_get_contents($file);
		return true;
	}
	
	function smarty_resource_widget_timestamp ($tpl_name, $tpl_timestamp, $smarty) {
	
		$file =$smarty->resolve_resource_widget($tpl_name);
		$tpl_timestamp =filemtime($file);
		return true;
	}
	
	function smarty_resource_widget_secure ($tpl_name, $smarty) {
		
		return true;
	}
	
	function smarty_resource_widget_trusted ($tpl_name, $smarty) {
		
		return true;
	}