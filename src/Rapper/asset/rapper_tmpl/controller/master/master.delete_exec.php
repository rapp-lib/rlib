
	//-------------------------------------
	// Action: 削除 実行
	public function act_delete_exec () {
		
		$this->context("c",1,true);
		
		if ($this->c->id()
				&& ! $this->c->session("complete")) {
				
			// データの削除
			<?=$model_obj?>->drop($this->c->id());
			
			$this->c->session("complete",true);
		}
		
		redirect("page:.view_list");
	}
