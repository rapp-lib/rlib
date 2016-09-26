<?php

	//-------------------------------------
	// ファイルアップロード設定
	registry("UserFile.user_file_dir", registry("Path.html_dir")."/user_file");
	registry("UserFile.group", array(
	
		"public" =>array(
			"upload_dir" =>registry("UserFile.user_file_dir").'/uploaded',
			"allow_ext" =>array(
				'jpg', 'jpeg', 'png', 'gif', 'pdf',
			),
			"hash_level" =>3,
			"save_raw_filename" =>false,
		),
		
		"private" =>array(
			"upload_dir" =>registry("Path.tmp_dir").'/uploaded',
			"allow_ext" =>array(
				'jpg', 'jpeg', 'png', 'gif',
				'zip', 'pdf', 'bmp',
				'csv', 'txt', 'xml',
				'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx',
			),
			"hash_level" =>3,
			"save_raw_filename" =>false,
		),
	));
	