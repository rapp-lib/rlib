<!?php

//-------------------------------------
// Controller: <?=$c["name"]?> 
class <?=str_camelize($c["name"])?>Controller extends Controller_App {

	//-------------------------------------
	// Action: index
	public function act_index () {
	
		redirect("page:.view_list");
	}

	//-------------------------------------
	// Action: view_list
	public function act_view_list () {
		
		$this->context("c",0);
		
		// リスト取得条件の消去
		if ($_REQUEST["reset"]) {
		
			$this->c->input(false,false);
		}
		
		// 入力情報の登録
		$this->c->input($_REQUEST["c"]);
		
		// リスト取得
		$list_setting =array(
			"search" =>array(
<? foreach ($this->filter_fields($t["fields"],"search") as $tc): ?>
				"<?=$tc['name']?>" =>array(
						"type" =>'eq',
						"target" =>"<?=$tc['name']?>"),
<? endforeach; ?>
			),
			"sort" =>array(
				"sort_param_name" =>"sort",
				"default" =>"<?=$t['pkey']?> ASC",
				"map" =>array(
<? foreach ($this->filter_fields($t["fields"],"sort") as $tc): ?>
					"<?=$tc['name']?>" =>"<?=$tc['name']?> ASC",
<? endforeach; ?>
				),
			),
			"paging" =>array(
				"limit" =>20,
				"offset_param_name" =>"offset",
			),
		);
		list($this->vars["ts"] ,$this->vars["p"])
				=model("<?=str_camelize($t["name"])?>")->get_list($list_setting,$this->c->input());
	}

	//-------------------------------------
	// action .detail
	public function act_view_detail () {
		
		$this->context("c");
		
		// idの指定
		$this->c->id($_REQUEST["id"]);
		
		// 登録データの取得
		$this->vars["t"] =model("<?=str_camelize($t["name"])?>")->get_by_id($this->c->id());
		
		// 既存データの取得ができない場合の処理
		if ( ! $this->vars["t"]) {
				
			$this->c->id(false);
		
			redirect("page:.view_list");
		}
	}

	//-------------------------------------
	// action .entry_form
	public function act_entry_form () {
		
		$this->context("c",1,true);
		
		// id指定があれば既存のデータを読み込む
		if ($_REQUEST["id"]) {
			
			// idの指定
			$this->c->id($_REQUEST["id"]);
			
			// 既存データの取得
			$input =model("<?=str_camelize($t["name"])?>")->get_by_id($this->c->id());
			
			// 既存データの取得ができない場合の処理
			if ( ! $input) {
				
				$this->c->id(false);
				
				redirect("page:.view_list");
			}
			
			// 既存の情報をフォームへ登録
			$this->c->input($input);
		}
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
		
		redirect("page:.entry_exec");
	}

	//-------------------------------------
	// action .entry_exec
	public function act_entry_exec () {
		
		$this->context("c",1,true);
		
		// 入力情報の登録
		if ( ! $this->c->errors()
				&& $this->c->session("checked")
				&& ! $this->c->session("complete")) {
			
			// データの記録
			$fields =$this->c->get_fields(array(
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
				"<?=$tc['name']?>",
<? endforeach; ?>
			));
			model("<?=str_camelize($t["name"])?>")->save($fields,$this->c->id());
			
			$this->c->session("complete",true);
		}
		
		redirect("page:.view_list");
	}
	
	//-------------------------------------
	// action .delete_confirm
	public function act_delete_confirm () {
		
		$this->context("c",1,true);
		
		// idの指定
		$this->c->id($_REQUEST["id"]);
			
		// 既存のデータを確認
		$input =model("<?=str_camelize($t["name"])?>")->get_by_id($this->c->id());
		
		// 既存データの確認ができない場合の処理
		if ( ! $input) {
		
			$this->c->id(false);
				
			redirect("page:.view_list");
		}
		
		redirect("page:.delete_exec");
	}

	//-------------------------------------
	// action .delete_exec
	public function act_delete_exec () {
		
		$this->context("c",1,true);
		
		if ($this->c->id()
				&& ! $this->c->session("complete")) {
				
			// データの削除
			model("<?=str_camelize($t["name"])?>")->delete($this->c->id());
			
			$this->c->session("complete",true);
		}
		
		redirect("page:.view_list");
	}
}