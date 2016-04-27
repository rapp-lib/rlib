<?php
		
/*
	-------------------------------------
	□メール送信用 options:
		・to/from/subject/messageの指定は必須
		・cc/bccの指定も可能
		・to/cc/bccは配列で複数指定が可能
		※to/cc/bccの名前を設定することは出来ません
		・template_fileとtemplate_optionsでテンプレートファイル読み込み
		・fromnameでfromの日本語名をつけられる
		・attach_files配列内にfilenameとdata_file（またはdata）を指定可能
		・error_handlerで配信エラー時に呼び出す関数を登録
		・send_mode=smtp/send_options=...で送信サーバの指定が可能
		※テンプレートファイルの書式
			・先頭にto/from/subjectを「subject: ***」形式で指定
			・ファイルの内容はPHPとしてパースされる
			・各項目記述の後、空行を開けて、以降にmessageを記述
		
	-------------------------------------
	□メール一斉配信予約用テーブル構成: 
		CREATE TABLE MailQueue (
			id bigint(20) NOT NULL default '0',
			create_time datetime NOT NULL default '0000-00-00 00:00:00',
			time_to_send datetime NOT NULL default '0000-00-00 00:00:00',
			sent_time datetime default NULL,
			id_user bigint(20) NOT NULL default '0',
			ip varchar(20) NOT NULL default 'unknown',
			sender varchar(50) NOT NULL default '',
			recipient text NOT NULL,
			headers text NOT NULL,
			body longtext NOT NULL,
			try_sent tinyint(4) NOT NULL default '0',
			delete_after_send tinyint(1) NOT NULL default '1',
			PRIMARY KEY  (id),
			KEY id (id),
			KEY time_to_send (time_to_send),
			KEY id_user (id_user)
		);

	-------------------------------------
	□サンプルコード（メール送信）:
		
		// テンプレートファイルの内容でメール送信
		obj("BasicMailer")->send_mail(array(
			"to" =>"toyosawa@sharingseed.co.jp",
			"from" =>"dev@sharingseed.info",
			"template_file" =>"./mail/default_mail.php",
			
			"send_mode" =>"smtp", // SMTPサーバを明示的に指定（オプション）
			"send_options" =>array(
				"host" =>"210.188.236.3",
				"port" =>"25",
				"auth" =>true,
				"username" =>"test@example.com",
				"password" =>"00000000",
			),
		));
		

	-------------------------------------
	□サンプルコード（一斉配信の予約と送信）:
		
		// 5件の配信を予約
		for ($i=0; $i<5; $i++) {
			
			obj("BasicMailer")->queue_mail(array(
				"time_to_send" =>time()+1*60*60, // 1時間後に配信
				"to" =>"toyosawa@sharingseed.co.jp",
				"from" =>"dev@sharingseed.info",
				"subject" =>"TestQueuedMail-".$i,
				"message" =>"TestQueuedMail".$i,
			));
		}
		
		// DBに登録されているメールを全件順次配信
		obj("BasicMailer")->send_queued_mail(array(
			"limit" =>MAILQUEUE_ALL, // 全件同時配信
			"send_mode" =>"smtp", // SMTPサーバを明示的に指定
			"send_options" =>array(
				"host" =>"210.188.236.3",
				"port" =>"25",
				"auth" =>true,
				"username" =>"test@example.com",
				"password" =>"00000000",
			),
		));

	-------------------------------------
	□サンプルコード（メール受信起動）:
		
		// 標準入力からMTAの受信データを読み込んでパース
		$received_mail =obj("BasicMailer")->receive_mail(array(
		));
*/

//-------------------------------------
// PEARメール送受信クラス
class BasicMailer {
	
	protected $email_regex_pattern ='{(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*}';
	
	//-------------------------------------
	// メール送信
	// Dep: Pear/Mail.php
	public function send_mail ($options=array()) {
		
		require_once("Mail.php");
		
		$options =$this->check_options_to_send($options);
		
		$options =$this->check_options($options,array(
			"send_mode" =>"mail",
			"send_options" =>array(),
		),array(
		));
		
		$mailer =Mail::factory($options["send_mode"],$options["send_options"]);
		
		if ( ! $this->error_check($mailer)) {
			
			report_error("Create mail-object failur");
		}
		
		// 配信実行
		$result =$mailer->send(
				$options["to"],
				$options["mime_headers"],
				$options["mime_body"]);
		$is_success =$this->error_check($result);
		
		// 送信後処理
		if ($options["callback"]
				&& is_callable($options["callback"])) {
			
			$options["result"] =$is_success;
			call_user_func($options["callback"],$options);
		}
			
		// 配信成功をレポート
		if ($is_success) {
			
			$trunc_message =$options["message"];
			
			if (strlen($trunc_message)>100) {
			
				$trunc_message =substr($trunc_message,0,100)
						."...(".(strlen($trunc_message)-100).'bytes remains)';
			}
			
			report("Send-mail completed",array(
				"to" =>$options["to"],
				"from" =>$options["fromname"].'<'.$options["from"].'>',
				"subject" =>$options["subject"],
				"message" =>$trunc_message,
			));
		}
		
		return $is_success;
	}
	
	//-------------------------------------
	// 一括メール送信予約
	// Dep: Pear/Mail_Queue.php
	public function queue_mail ($options=array()) {
		
		// DB接続はContainer拡張でDBIを使用しています
		$options =$this->check_options_to_send($options);
		$options =$this->check_options_to_queue($options);
		$options =$this->check_options($options,array(
			"time_to_send" =>time(),
		),array(
		));
		
		$result =$options["mail_queue"]->put(
				$options["from"], 
				$options["to"], 
				$options["mime_headers"], 
				addslashes($options["mime_body"]),
				$options["time_to_send"]-time());
				
		return is_numeric($result)
				? $result
				: null;
	}
	
	//-------------------------------------
	// 予約済みの一括メール配信
	// Dep: Pear/Mail_Queue.php
	public function send_queued_mail ($options=array()) {
		
		$options =$this->check_options_to_queue($options);
		$options =$this->check_options($options,array(
			"limit" =>MAILQUEUE_ALL,
			"offset" =>MAILQUEUE_START,
			"try" =>3,
			"callback" =>null,
		),array(
		));
		
		$result =$options["mail_queue"]->sendMailsInQueue(
				$options["limit"],
				$options["offset"],
				$options["try"],
				$options["callback"]);
		
		return $result === true;
	}
	
	//-------------------------------------
	// メール受信
	// Dep: Pear/Mail/mimeDecode.php
	public function receive_mail (array $options=array()) {
		
		// PEARメール解析機能読み込み
		require_once("Mail/mimeDecode.php");
		
		$options =$this->check_options($options,array(
			"receive_mode" =>"file",
			"mail_data" =>"",
			"mail_data_file" =>"php://stdin",
			"receive_option" =>array(),
		),array(
		));
		
		$raw_mail ="";
		
		if ($options["receive_mode"]=="file") {
		
			$raw_mail =file_get_contents($options["mail_data_file"]);
			
		} elseif ($options["receive_mode"]=="data") {
		
			$raw_mail =$options["mail_data"];
			
		} else {
			
			report_error("Invalid receive_mode: ".$options["receive_mode"]);
		}
		
		$decoder =new Mail_mimeDecode($raw_mail."\n");
		$decoded =$decoder->decode(array(
				'include_bodies' =>true,
				'decode_bodies' =>true,
				'decode_heders' =>true));
		
		$maildata =array();
		
		// HEAD部解析
		foreach ((array)$decoded->headers as $k => $v) {
			
			$maildata[strtolower($k)] =is_string($v)
					? mb_decode_mimeheader($v)
					: $v;
		}
		
		// from抽出
		if ($maildata["from"]) {
			
			$from_mail_parts =$this->extract_email($maildata["from"]);
			$maildata["from"] =$from_mail_parts[0];
		}
		
		// BODY部解析
		if ($decoded->parts) {
			
			foreach ($decoded->parts as $i => $part) {
				
				$this->decode_body($decoded->parts[$i],$maildata,$i==0);
			}
			
		} else {
		
			$this->decode_body($decoded,$maildata,true);
		}
		
		return $maildata;
	}
	
	//-------------------------------------
	// メールアドレスのみ抜き出し
	public function extract_email ($raw_string) {
		
		$result =preg_match_all(
				$this->email_regex_pattern, 
				$raw_string, 
				$matches, 
				PREG_PATTERN_ORDER);
				
		if ($result && $matches[0]) {
			
			return $matches[0];
		}
		
		return array();
	}
	
	//-------------------------------------
	// パラメータ生成用テキスト解析
	protected function parse_mail_template (
			$param_text,
			$params =array(),
			$params_input=true) {
			
		foreach (explode("\n",$param_text) as $line) {
			
			if ($params_input) {
				
				if (preg_match('!^(\w+)\s*:\s*(.*?)$!i',trim($line),$match)) {
					
					if ($params_input === "overwrite" 
							|| !  isset($params[strtolower($match[1])])) {
							
						$params[strtolower($match[1])] =$match[2];
					}		
				
				} else {
				
					$params_input =false;
				}
			
			} else {
				
				$params["message"] .=$line."\n";
			}
		}
		
		return $params;
	}
	
	//-------------------------------------
	// 本文解析
	protected function decode_body (&$obj, &$maildata, $is_message=false) {
		
		if ($obj->body) {
			
			// 本文
			if ($obj->ctype_primary=='text') {
				
				if ($obj->ctype_secondary == 'plain') {
				
					$maildata["message"] 
							.=trim(mb_convert_encoding($obj->body,'UTF-8',$this->get_mail_charset()));
				
				} else {
				
					$maildata["message_".$obj->ctype_secondary] 
							.=trim(mb_convert_encoding($obj->body,'UTF-8',$this->get_mail_charset()));
				}
				
			// 添付ファイル
			} else {
			
				$maildata["attach_files"][] =array(
					'mimetype'=>$obj->ctype_primary.'/'.$obj->ctype_secondary,
					'filename'=>$obj->ctype_parameters['name'],
					'data'=>$obj->body,
				);
			}
		}
	}
	
	//-------------------------------------
	// PEARエラーチェック
	protected function error_check ($object) {
		
		if (PEAR::isError($object)) {
			
			report_warning('PEAR-ERROR: '.$object->getMessage());
			
			return false;
		}
		
		return true;
	}
	
	//-------------------------------------
	// パラメータの確認
	protected function check_options (
			$options, 
			$default_values=array(), 
			$required=array()) {
		
		$errors =array();
		
		foreach ($default_values as $k => $v) {
			
			if (is_null($options[$k])) {
				
				$options[$k] =$v;
			}
		}
		
		foreach ($required as $k => $v) {
			
			if (is_null($options[$k])) {
				
				$errors[] ='Option-required: '.$k;
			}
		}
		
		if ($errors) {
			
			report_error('CheckOption-failur: '.decorate_value($errors));
		}
		
		return $options;
	}
	
	//-------------------------------------
	// メール送信用のOption構築
	// Dep: Pear/Mail/mime.php
	protected function check_options_to_send ($options=array()) {
		
		require_once("Mail/mime.php");
		
		mb_language("japanese");
		mb_internal_encoding("UTF-8");
		
		// テンプレートファイルの読み込み
		if ($options["template_file"] 
				&& file_get_contents($options["template_file"])) {
			
			extract($options["template_options"],EXTR_SKIP);
			ob_start();
			include($options["template_file"]);
			$template_text =ob_get_clean();
			
			$options =$this->parse_mail_template($template_text,$options);
		}
		
		$options =$this->check_options($options,array(
			"fromname" =>"",
			"attach_files" =>array(),
			"headers" =>array(),
			"cc" =>null,
			"bcc" =>null,
		),array(
			"to" =>true,
			"subject" =>true,
			"message" =>true,
			"from" =>true,
		));
		
		// SMTPへ送信元のパラメータを付加
		$options["send_options"][] ="-f ".$options["from"];
		
		$message =mb_convert_encoding($this->convert_kana($options["message"]),
                $this->get_mail_charset(),"UTF-8");
		$subject =mb_encode_mimeheader($this->convert_kana($options["subject"]),
                $this->get_mail_charset(), "B", "\n");
		
		$fromname =mb_encode_mimeheader($this->convert_kana($options["fromname"]), 
                $this->get_mail_charset(), "B", "\n");
		$from =strlen($options["fromname"]) 
				? $fromname."<".$options["from"].">" 
				: $options["from"];
			
		$mime =new Mail_Mime("\n");
		
		// 本文の76桁自動改行処理
		$message_wrap = "";
		$count_col = 0;
		
		for ($i=0; $i<mb_strlen($message, $this->get_mail_charset()); $i++) {
			
			$substr= mb_substr($message, $i, 1, $this->get_mail_charset());
			$message_wrap .= $substr;
			
			// 改行コードを検知した場合は、桁数をリセット
			if($substr == "\n") { $count_col = 0; }
			
			// 切り出された文字のバイト数
			$count_col += strlen($substr)==1 ? 1 : 2;
			
			// 76桁目で改行コードを挿入
			if($count_col >= 76) {
			
				$count_col=0;
				$message_wrap .= "\n";
			}
		}
		
		$message =$message_wrap;
		
		// 本文登録
		$mime->setTxtBody($message);
		
		// 添付ファイル登録
		foreach ($options["attach_files"] as $attach_file) {
		
			if ( ! $attach_file["filename"]) {
				
				continue;
			}
			
			$filename =mb_encode_mimeheader($attach_file["filename"]);
			$mimetype =$attach_file["mimetype"]
					? $attach_file["mimetype"]
					: "application/octet-stream";
					
			if ($attach_file["data"]) {
			
				$mime->addAttachment(
						$attach_file["data"],
						$mimetype,
						$filename,
						false);
			
			} elseif (file_exists($attach_file["data_file"])) {
					
				$mime->addAttachment(
						$attach_file["data_file"],
						$mimetype,
						$filename,
						true);
			}
		}
		
		// HEADER構築
		$headers =(array)$options["headers"];
		$headers["From"] =$from;
		$headers["Subject"] =$subject;
		$headers["Bcc"] =$options["bcc"];
		$headers["Cc"] =$options["cc"];
		$headers["To"] =$options["to"];
		$headers =array_filter($headers,"strlen");
		
		// BODY部取得
		$options["mime_body"] =$mime->get(array(
			"head_charset" =>$this->get_mail_charset(),
			"text_charset" =>$this->get_mail_charset(),
		));
		
		// HEADERS部取得
		$options["mime_headers"] =$mime->headers($headers);
		
		// SMTP送信の場合、CC,BCCを送信先に加える必要があるための処理
		if ($options["send_mode"] == "smtp"
				&& ($options["cc"] || $options["bcc"])) {
		
			$options["to"] =is_array($options["to"]) 
					? $options["to"]
					: explode(",",$options["to"]);
					
			if ($options["cc"]) {
				
				$options["cc"] =is_array($options["cc"]) 
						? $options["cc"]
						: explode(",",$options["cc"]);
				$options["to"] =array_merge($options["to"],$options["cc"]);
			}
			
			if ($options["bcc"]) {
				
				$options["bcc"] =is_array($options["bcc"]) 
						? $options["cc"]
						: explode(",",$options["cc"]);
				$options["to"] =array_merge($options["to"],$options["bcc"]);
			}
		}
		
		return $options;
	}
	
	//-------------------------------------
	// メール一斉配信用のOption構築
	// Dep: Pear/Mail/Queue.php
	protected function check_options_to_queue ($options=array()) {
	
		require_once("Mail/Queue.php");
		
		$options =$this->check_options($options,array(
			"send_mode" =>"mail",
			"send_options" =>array(),
			
			"table" =>"MailQueue",
			"db_options" =>array(),
		),array(
		));
		
		$options["db_options"]['type'] ="dbi";
		$options["db_options"]['mail_table'] =$options['table'];
		$options["send_options"]['driver'] =$options["send_mode"];
		
		$options["mail_queue"] =& new Mail_Queue(
				$options["db_options"], 
				$options["send_options"]);
		
		return $options;
	}
	
	//-------------------------------------
	// メールで使用する文字コードの取得
	protected function get_mail_charset () {
		
		// UTF-8やJIS-MSはリスクが大きいので一部文字化けるがISO-2022-JPが最適
		$mail_charset =registry("Config.mail_charset");
		$mail_charset =$mail_charset
				? $mail_charset
				: "ISO-2022-JP";
		
		return $mail_charset;
	}
	
	//-------------------------------------
	// メールで使用する半角カナの置換
	protected function convert_kana ($value) {
		
		if ($this->get_mail_charset() == "ISO-2022-JP") {
            
            $value =mb_convert_kana($value,"KV");
        }
		
		return $value;
	}
    
    
}
