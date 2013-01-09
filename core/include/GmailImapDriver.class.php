<?php

//-------------------------------------
// GmailへIMAPで接続するクラス
class GmailImapDriver {
	
	protected $mbox;
	protected $account;
	protected $password;
	protected $mref;
	protected $mref_base ="{imap.gmail.com:993/novalidate-cert/imap/ssl}";
	
	//-------------------------------------
	// 初期化
	public function __construct (
			$account, // Gmailアカウント
			$password, // パスワード
			$mref="") { // メールボックス
		
		mb_internal_encoding("UTF-8");
		
		$this->account =$account;
		$this->password =$password;
		$this->connect($mref);
	}

	//-------------------------------------
	// デストラクタ
	public function __destruct () {
		
		if ($this->mbox) {
			
			imap_close($this->mbox);
		}
	}

	//-------------------------------------
	// IMAP接続
	public function connect ($mref="") {
			
		$this->mref =preg_match('!^\{!',$mref)
				? $mref
				: $this->mref_base.$mref;
		
		$this->mref =mb_convert_encoding($this->mref,"UTF7-IMAP","UTF-8");
		
		$this->mbox =@imap_open($this->mref, $this->account, $this->password);
		
		if ( ! $this->mbox) { throw new Exception("ERROR:".imap_last_error()); }
	}

	//-------------------------------------
	// メールボックス一覧の取得
	public function get_mrefs ($ptn="*") {
		
		$mrefs =array();
		
		foreach (imap_lsub($this->mbox,$this->mref,$ptn) as $mref) {
			
			$mref =mb_convert_encoding($mref,"UTF-8","UTF7-IMAP");
			$mref =preg_replace('!^'.preg_quote($this->mref_base,"!").'!',"",$mref);
			$mrefs[] =$mref;
		}
		
		return $mrefs;
	}
	
	//-------------------------------------
	// get_mailsに渡すoffsetの最大値の取得（最新のメール取得に使用する）
	public function get_mail_max_offset () {
		
		$this->mboxes =@imap_check($this->mbox);
		
		if ( ! $this->mboxes) { throw new Exception("ERROR:".imap_last_error()); }
		
		$max_offset =$this->mboxes->Nmsgs;
		
		return $max_offset;
	}
	
	//-------------------------------------
	// メール一覧を取得
	public function fetch_mails ($offset=0, $volume=20) {
		
		if ($volume > 1000) { throw new Exception("ERROR: cannot fetch 1000+ mails"); }
	
		$fetch_from =$offset;
		$fetch_to =$offset + $volume - 1;
		$fetch_range =$fetch_from.":".$fetch_to;
		
		$overview_list =imap_fetch_overview($this->mbox,$fetch_range,0);
		
		$mails =array();
		
		foreach ($overview_list as $overview) {
			
			$mail =array();
			
			$id =$overview->msgno;
			
			$mail["id"] =$id;
			$mail["date"] =date("Y-m-d H:i:s", strtotime($overview->date));
			$mail["is_reply"] =$overview->in_reply_to;
			
			$head =imap_headerinfo($this->mbox,$id);
			$to =$head->to[0];
			
			$mail["to"] =$to->mailbox."@".$to->host;
			$mail["to_domain"] =$to->host;
			$mail["to_name"] =mb_decode_mimeheader($to->personal);
			
			$mails[] =$mail;
		}
		
		return $mails;
	}
	
	//-------------------------------------
	// メッセージ部分を取得（UTF-8/LFフォーマット）
	public function fetch_message ($id) {
		
		$structure =imap_fetchstructure($this->mbox,$id);
		$message =$structure->type
				? imap_fetchbody($this->mbox,$id,"1")
				: imap_body($this->mbox,$id);
				
		$message =mb_convert_encoding($message,"UTF-8","JIS");
		$message =str_replace("\r\n","\n",$message);
		$message =str_replace("\r","\n",$message);
		
		return $message;
	}
	
	//-------------------------------------
	// メッセージを解析して宛先名を抽出
	public function parse_message_to ($message) {
		
		$message =str_replace("\n"," ",$message);
		
		$mail =array();
		$mail["name_detect"] ="";
		$mail["sir_detect"] ="様";
		
		if (preg_match('!^\s*(.{0,20}?)(さん|様)!u',,$match)
				&& $match[1] != "お疲れ" && $match[1] != "おつかれ") {
				
			$mail["name_detect"] =$match[1];
			$mail["sir_detect"] =$match[2];
		}
		
		return $mail;
	}
}