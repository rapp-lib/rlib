<?php

	//-------------------------------------
	// ファイルアップロード
	registry(array(
		
		// アップロードディレクトリの指定
		"UserFileManager.upload_dir" =>array(
			"default" =>registry("Path.html_dir").'/user_file/uploaded',
			"group" =>array(
				"data" =>registry("Path.tmp_dir").'/uploaded',
			),
		),
		
		// ファイル拡張子制限
		"UserFileManager.allow_ext" =>array(
			"default" =>array(
				'jpg', 'jpeg', 'png', 'gif',
			),
			"group" =>array(
				"data" =>array(
					'jpg', 'jpeg', 'png', 'gif',
					'zip', 'pdf', 'bmp',
					'csv', 'txt', 'xml',
					'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx',
				),
			),
		),
		
		// ハッシュ階層指定
		"UserFileManager.hash_level" =>array(
			"default" =>3,
			"group" =>array(
				"image" =>3,
				"data" =>3,
			),
		),
		
		// アップロードディレクトリの指定
		"ImageResize.resized_image_dir" =>array(
			"default" =>registry("Path.html_dir").'/user_file/resized',
		),
	));