<?php

	//-------------------------------------
	// ルーティング設定
	registry(array(
	
		"Routing.page_to_path" =>array(
			
			// TOPページ
			"index.index" =>"/index.html",
		),
		
		// HTTPアクセス制限
		"Routing.force_https.zone" =>array(
		),
		"Routing.force_https.safe_zone" =>array(
		),
	));