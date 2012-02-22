<?php

	define('RLIB_ROOT_DIR',dirname(__FILE__));
	define('LIBVER',111116);
	
	foreach (glob(RLIB_ROOT_DIR."/core/*.php") as $filename) {
		
		require_once($filename);
	}
	
	add_include_path(RLIB_ROOT_DIR.'/core/include/');
	add_include_path(RLIB_ROOT_DIR.'/core/pear/');
	