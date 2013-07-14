<?php

	//-------------------------------------
	// ルーティング設定
	registry(array(
	
		"Routing.page_to_path" =>array(
			
			// TOPページ
			"index.index" =>"/index.html",
			
			// 404エラーページ
			"static.error_404" =>"/error/error_404.jsp",
		),
		
		// HTTPアクセス制限
		"Routing.access_only.https" =>array(
		),
	));