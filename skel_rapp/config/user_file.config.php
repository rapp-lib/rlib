<?php

	//-------------------------------------
	// ファイルアップロード
	registry(array(
		
		// アップロードディレクトリの指定
		"UserFileManager.upload_dir" =>array(
			"default" =>registry("Path.html_dir").'/user_file/uploaded',
			"group" =>array(
				"image" =>registry("Path.html_dir").'/user_file/uploaded',
				"data" =>registry("Path.tmp_dir").'/uploaded',
			),
		),
		
		// ファイル拡張子制限
		"UserFileManager.allow_ext" =>array(
			"default" =>array(
				'jpg', 'jpeg', 'png', 'gif', 'bmp',
			),
			"group" =>array(
				"image" =>array(
					'jpg', 'jpeg', 'png', 'gif', 'bmp',
				),
				"data" =>array(
					'jpg', 'jpeg', 'png', 'gif', 'bmp',
					'zip', 'pdf', 
					'csv', 'txt', 'xml',
					'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx',
				),
			),
		),
		
		// アップロードディレクトリの指定
		"ImageResize.resized_image_dir" =>array(
			"default" =>registry("Path.html_dir").'/user_file/resized',
		),
	));