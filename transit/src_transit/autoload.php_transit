<?php
	
	require_once(dirname(__FILE__).'/Core/ClassLoader.php');
	use R\Lib\Core\ClassLoader;

	ClassLoader::install();
	ClassLoader::add("R\\Lib" ,dirname(__FILE__));
	
	if (defined("APPLIB_DIR")) {
		
		ClassLoader::add("R\\Lib" ,constant("APPLIB_DIR"));
	}
	
	if (defined("WEBAPP_DIR")) {
		
		ClassLoader::add("R\\Lib" ,constant("WEBAPP_DIR").'/app/include');
	}
