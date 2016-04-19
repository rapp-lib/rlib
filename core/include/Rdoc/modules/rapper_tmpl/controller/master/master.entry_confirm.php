

	//-------------------------------------
	// Action: フォーム 確認
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
		
<? if ($c["usage"] != "form"): ?>
		redirect("page:.entry_exec");
<? endif; ?>
	}