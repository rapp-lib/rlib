<?php


//-------------------------------------
// PEARメール送受信クラス
class BasicMailer {
	
	protected $default_options =array();
	
	protected $email_regex_pattern ='/(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*/';
	
	//-------------------------------------
	// 初期化
	public function __construct ($options=array()) {
		
		$this->default_options =$this->check_options($options,array(
			"send_mode" =>"mail",
			"send_options" =>array(),
			"receive_mode" =>"stdin",
			"receive_option" =>array(),
		),array(
		));
	}
	
	//-------------------------------------
	// メール送信
	// Dep: Pear/Mail/mime.php
	// Dep: Pear/Mail.php
	public function send_mail ($options=array()) {
		
		/*
			options:
			・to/from/subject/messageの指定は必須
			・template_fileとtemplate_optionsでテンプレートファイル読み込み
			・fromnameでfromの日本語名をつけられる
			・attach_files配列内にfilenameとdata_file（またはdata）を指定可能
		*/
		
		mb_language("japanese");
		mb_internal_encoding("UTF-8");
		
		// PEARメール送信機能読み込み
		require_once("Mail.php");
		require_once("Mail/mime.php");
		
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
		),array(
			"to" =>true,
			"subject" =>true,
			"message" =>true,
			"from" =>true,
		));
		
		// SMTPへ送信元のパラメータを付加
		$options["send_options"][] ="-f ".$options["from"];
		
		$mailer =Mail::factory($options["send_mode"],$options["send_options"]);
		
		if ( ! $this->error_check($mailer)) {
			
			report_error("Create mail-object failur");
		}
		
		// コード変換
		$message =mb_convert_encoding($options["message"],"JIS","UTF-8");
		$fromname =mb_encode_mimeheader($options["fromname"]);
		$subject =mb_encode_mimeheader($options["subject"]);
		
		$from =strlen($fromname) 
				? $fromname."<".$options["from"].">" 
				: $options["from"];
			
		$mime =new Mail_Mime("\n");
		
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
		
		// BODY部取得
		$body =$mime->get(array(
			"head_charset" => "ISO-2022-JP",
			"text_charset" => "ISO-2022-JP"
		));
		
		// HEADERS部取得
		$headers =$mime->headers(array(
			"From" =>$from,
			"Subject" =>$subject
		));
		
		$result =$mailer->send($options["to"],$headers,$body);
		$is_success =$this->error_check($result);
		
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
	// メール受信
	// Dep: Pear/Mail/mimeDecode.php
	public function receive_mail (array $options=array()) {
		
		// PEARメール解析機能読み込み
		require_once("Mail/mimeDecode.php");
		
		$options =$this->check_options($options,array(
		),array(
		));
		
		$raw_mail ="";
		
		if ($options["receive_mode"]=="stdin") {
		
			$raw_mail =file_get_contents("php://stdin");
			
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
		
			$maildata[strtolower($k)] =mb_convert_encoding(
					mb_decode_mimeheader($this->getRawHeader($v)),'UTF-8','JIS');
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
	protected function decode_body (&$decoded, &$maildata, $is_message=false) {
		
		if ($decoded->body) {
			
			// 本文
			if ($is_message && strtolower($decoder->ctype_primary)=='text') {
			
				$maildata["message"] =&$decoder->body;
				
			// 添付ファイル
			} else {
			
				$maildata["attach_files"][] =array(
					'mimetype'=>$decoder->ctype_primary.'/'.$decoder->ctype_secondary,
					'filename'=>$decoder->ctype_parameters['name'],
					'data'=>&$decoder->body,
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
		
		foreach ($this->default_options as $k => $v) {
			
			if (is_null($options[$k])) {
				
				$options[$k] =$v;
			}
		}
		
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
}