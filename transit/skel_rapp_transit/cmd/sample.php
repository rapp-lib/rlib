<?php

	include_once(dirname(__FILE__)."/../config/config.php");
	
	__start();
	exit;	
	
	//-------------------------------------
	// __start
	function __start () {
		
		set_time_limit(0);
		registry("Report.force_reporting",true);
		
		register_shutdown_webapp_function("__end");
		start_webapp();
		
		$params =get_cli_params();
		
		/// 実装
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
	}