<!?php

//-------------------------------------
// Controller: <?=$c["name"]?> 
class <?=str_camelize($c["name"])?>Controller extends Controller_App {

	//-------------------------------------
	// Action: index
	public function act_index () {
	
		redirect("page:.entry_form");
	}

	//-------------------------------------
	// action .entry_form
	public function act_entry_form () {
		
		$this->context("c",1,true);
	}

	//-------------------------------------
	// action .entry_confirm
	public function act_entry_confirm () {
		
		$this->context("c",1,true);

		// 入力情報の登録
		$this->c->input($_REQUEST["c"]);
		
		// 入力チェック
		$this->c->validate(array(
		),array(
		));
		
		// 入力情報のチェック確認フラグ
		$this->c->session("checked",true);
		
		// 入力エラー時の処理
		if ($this->c->errors()) {
			
			redirect("page:.entry_form");
		}
		
		$this->vars["t"] =$this->c->input();
	}

	//-------------------------------------
	// action .entry_exec
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
				// "template_file" =>registry("Path.webapp_dir")
				// 		."/app/mail/<?=$c["name"]?>_mail.php"),
				// "template_options" =>$this->c->input(),
			));
<? else: ?>
			// データの記録
			$fields =$this->c->get_fields(array(
<? foreach ($t["fields"] as $tc): ?>
				"<?=$tc['name']?>",
<? endforeach; ?>
			));
			Model::load("<?=str_camelize($t["name"])?>")->save_<?=str_underscore($t["name"])?>($fields);
<? endif; ?>

			$this->c->session("complete",true);
		}
	}
}