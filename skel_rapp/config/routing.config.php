<?php

	//-------------------------------------
	// ルーティング設定
	registry(array(
	
		"Routing.page_to_path" =>array(
			
			// TOPページ
			"index.index" =>"/index.html",
		),
		
		// HTTPアクセス制限
		"Routing.force_https.area" =>array(
		),
		
		// Vifアクセス制御
		"Routing.force_vif.enable" =>false,
		/*
		"Routing.force_vif.target" =>array(
			"sample" =>array(
				"path" =>"/sample_frame.html",
				"area" =>array(
				),
			),
		),
		*/
	));