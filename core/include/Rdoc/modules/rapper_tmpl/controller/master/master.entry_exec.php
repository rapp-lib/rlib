

	//-------------------------------------
	// Action: フォーム 登録実行
	public function act_entry_exec () {
		
		$this->context("c",1,true);
		
		// 入力情報の登録
		if ( ! $this->c->errors()
				&& $this->c->session("checked")
				&& ! $this->c->session("complete")) {
			
<? if ($t["virtual"]): ?>
			// メールの送信
			obj("BasicMailer")->send_mail(array(
				"to" =>"test@example.com",
				"from" =>"test@example.com",
				"subject" =>"Test mail",
				"message" =>"This is test.",
				// "template_file" =>"/mail/<?=$c["name"]?>_mail.php",
				// "template_options" =>array("c" =>$this->c->input()),
			));
<? else: /* $t["virtual"] */ ?>
			// データの記録
			$fields =$this->c->get_fields(array(
<? foreach ($r->get_fields($c["_id"],"input") as $f): ?>
				"<?=$f['name']?>",
<? endforeach; ?>
			));
			<?=$model_obj?>->save($fields,$this->c->id());
<? endif; /* $t["virtual"] */ ?>
			
			$this->c->session("complete",true);
		}
		
<? if ($c["usage"] != "form"): ?>
		redirect("page:.view_list");
<? endif; ?>
	}