<?php

	//-------------------------------------
	// ルーティング設定
	registry(array(
	
		"Routing.page_to_path" =>array(
			
			// TOPページ
			"index.index" =>"/index.html",
		),
		
		// HTTPアクセス制限
		"Routing.access_only.https" =>array(
		),
	));