<?php

	//
	// □ メール受信とファイルアップロードのサンプル
	//    postfixなどMTAでforward設定して使用します
	// 
	// ~/.forward
	// 		"| /usr/bin/php -q /path_to_webapp/cmd/mail_forward.php"
	// 
	// test-case: 
	// 		php -q ./mail_forward.php < ./sample.eml
	//
	
	chdir(dirname(__FILE__));
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
		
		$received_mail =obj("BasicMailer")->receive_mail(array(
		));
		
		$mail_from =$received_mail["from"];
		$file_data =$received_mail["attach_files"][0]["data"];
		$file_mime =$received_mail["attach_files"][0]["mimetype"];
		
		$file_size =strlen($file_data);
		
		$code =obj("UserFileManager")->upload_data($file_data);
		
		obj("BasicMailer")->send_mail(array(
			"to" =>$mail,
			"template_file" =>"../mail/upload_reply_mail.php",
			"template_options" =>array(
				"mail" =>$mail_from,
				"code" =>$code,
				"file_mime" =>$file_mime,
				"file_size" =>$file_size,
			),
		));
		
		shutdown_webapp("normal");
	}
	
	//-------------------------------------
	// __end
	function __end ($cause, $options) {
	}