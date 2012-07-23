<?php

	define('RLIB_ROOT_DIR',dirname(__FILE__));
	define('LIBVER',111116);
	
	date_default_timezone_set('Asia/Tokyo');
	
	if ( ! defined("E_DEPRECATED")) {
		
		define("E_DEPRECATED",8192);
		define("E_USER_DEPRECATED",16384);
	} 
 
	foreach (glob(RLIB_ROOT_DIR."/core/*.php") as $filename) {
		
		require_once($filename);
	}
	
	add_include_path(RLIB_ROOT_DIR.'/core/include/');
	add_include_path(RLIB_ROOT_DIR.'/core/pear/');
	