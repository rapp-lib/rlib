<?php

	//-------------------------------------
	// DB接続
	registry(array(
		
		"DBI.connection" =>array(
			"default" =>array(
				'driver' => 'mysql',
				'encoding' => 'utf8',
				'persistent' => true,
				'prefix' => '',
				'host' => 'localhost',
				'database' => 'test',
				'login' => 'dev',
				'password' => 'pass',
			),
		),
				
		"DBI.statement.default_join_type" =>"LEFT",
	));
		
		