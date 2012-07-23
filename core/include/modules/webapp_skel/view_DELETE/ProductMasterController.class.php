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
}