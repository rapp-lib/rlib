<?php
	if ( ! function_exists("_model_instance")) {
		function _model_instance($t,$c) {
			$_ ='model("'.str_camelize($t["name"]).'"';
			if ($c["accessor"]) {
				$_ .=',"'.$c["accessor"].'"';
			}
			$_ .=')';
			return $_;
		}
	}
?><!?php

//-------------------------------------
// Controller: <?=$c["label"]?> 
class <?=str_camelize($c["name"])?>Controller extends Controller_App {

<? if ($c["usage"] != "form"): /* ------------------- list_setting ------------------ */ ?>
	//-------------------------------------
	// 検索フォーム設定
	protected $list_setting =array(
		"search" =>array(
<? foreach ($this->filter_fields($t["fields"],"search") as $tc): ?>
			"<?=$tc['name']?>" =>array(
					"type" =>'eq',
					"target" =>"<?=$tc['name']?>"),
<? endforeach; ?>
		),
		"sort" =>array(
			"sort_param_name" =>"sort",
			"default" =>"<?=$t['pkey']?>@ASC",
		),
		"paging" =>array(
			"offset_param_name" =>"offset",
			"limit" =>20,
			"slider" =>10,
		),
	);
	
<? endif /* $c["usage"] != "form" */ ?>
<? if($c["use_csv"]): /* ------------------- csv_setting ------------------ */ ?>
	//-------------------------------------
	// CSV設定
	protected $csv_setting = array(
		"file_charset" =>"SJIS-WIN",
		"data_charset" =>"UTF-8",
		"rows" =>array(
			"<?=$t['pkey']?>" =>"#ID",
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
			"<?=$tc['name']?>" =>"<?=$tc['label']?>",
<? endforeach; ?>
		),
		"filters" =>array(
			array("filter" =>"sanitize"),
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
<? if ($tc['list']): ?>
			array("target" =>"<?=$tc['name']?>",
					"filter" =>"list_select", 
<? if ($tc['type'] == "checklist"): ?>
					"delim" =>"/", 
<? endif; /* $tc['type'] == "checklist" */ ?>
					"list" =>"<?=$tc['list']?>"),
<? endif; /* $tc['list'] */ ?>
<? if ($tc['type'] == "date"): ?>
			array("target" =>"<?=$tc['name']?>",
					"filter" =>"date"),
<? endif; /* $tc['type'] == "date" */ ?>
<? endforeach; ?>
			array("filter" =>"validate",
					"required" =>array(),
					"rules" =>array()),
		),
		"ignore_empty_line" =>true,
	);
	
<? endif; /* $c["use_csv"] */ ?>
	//-------------------------------------
	// Action: トップ
	public function act_index () {
	
<? if ($c["usage"] == "form"): ?>
		redirect("page:.entry_form");
<? else: ?>
		redirect("page:.view_list");
<? endif; ?>
	}
	
<? if ($c["usage"] != "form"): /* ------------------- act_view_* ------------------ */ ?>
	//-------------------------------------
	// Action: 一覧
	public function act_view_list () {
		
		$this->context("c",0);
		
		// リスト取得条件の消去
		if ($_REQUEST["reset"]) {
		
			$this->c->input(false,false);
		}
		
		// 入力情報の登録
		$this->c->input($_REQUEST["c"]);
		list($this->vars["ts"] ,$this->vars["p"]) =<?=_model_instance($t,$c)?> 
				->get_by_search_form($this->list_setting,$this->c->input());
	}

	//-------------------------------------
	// Action: 詳細表示
	public function act_view_detail () {
		
		$this->context("c");
		
		// idの指定
		$this->c->id($_REQUEST["id"]);
		
		// 登録データの取得
		$this->vars["t"] =<?=_model_instance($t,$c)?>->get_by_id($this->c->id());
		
		// 既存データの取得ができない場合の処理
		if ( ! $this->vars["t"]) {
				
			$this->c->id(false);
		
			redirect("page:.view_list");
		}
	}
	
<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): /* ------------------- act_entry_* ------------------ */ ?>
	//-------------------------------------
	// Action: フォーム 入力
	public function act_entry_form () {
		
		$this->context("c",1,true);
		
		// 完了後の再アクセス時にはデータ消去
		if ($this->c->session("complete")) {
			
			$this->c->session(false, false);
		}
		
		// id指定があれば既存のデータを読み込む
		if ($_REQUEST["id"]) {
			
			// idの指定
			$this->c->id($_REQUEST["id"]);
			
			// 既存データの取得
			$input =<?=_model_instance($t,$c)?>->get_by_id($this->c->id());
			
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
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
				"<?=$tc['name']?>",
<? endforeach; ?>
			));
			<?=_model_instance($t,$c)?>->save($fields,$this->c->id());
<? endif; /* $t["virtual"] */ ?>
			
			$this->c->session("complete",true);
		}
		
<? if ($c["usage"] != "form"): ?>
		redirect("page:.view_list");
<? endif; ?>
	}
	
<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] == ""): /* ------------------- act_delete_* ------------------ */ ?>
	//-------------------------------------
	// Action: 削除 確認
	public function act_delete_confirm () {
		
		$this->context("c",1,true);
		
		// idの指定
		$this->c->id($_REQUEST["id"]);
			
		// 既存のデータを確認
		$input =<?=_model_instance($t,$c)?>->get_by_id($this->c->id());
		
		// 既存データの確認ができない場合の処理
		if ( ! $input) {
		
			$this->c->id(false);
				
			redirect("page:.view_list");
		}
		
		redirect("page:.delete_exec");
	}

	//-------------------------------------
	// Action: 削除 実行
	public function act_delete_exec () {
		
		$this->context("c",1,true);
		
		if ($this->c->id()
				&& ! $this->c->session("complete")) {
				
			// データの削除
			<?=_model_instance($t,$c)?>->drop($this->c->id());
			
			$this->c->session("complete",true);
		}
		
		redirect("page:.view_list");
	}

<? endif; /* $c["usage"] == "" */ ?>
<? if($c["usage"] != "form" && $c["use_csv"]): /* ------------------- act_view_csv ------------------ */ ?>
	//-------------------------------------
	// Action: CSVダウンロード
	public function act_view_csv () {
		
		set_time_limit(0);
		registry("Report.error_reporting",E_USER_ERROR|E_ERROR);
	    
		$this->context("c",1);
	    
		$res =<?=_model_instance($t,$c)?> 
				->get_by_search_form($this->list_setting,$this->c->input(),true);
		
		// CSVファイルの書き込み準備
		$csv_filename =registry("Path.tmp_dir")
				."/csv_output/<?=$t["name"]?>-"
				.date("Ymd-His")."-"
				.sprintf("%04d",rand(0,9999)).".csv";
		$csv =new CSVHandler($csv_filename,"w",$this->csv_setting);
		
		while (($t =$res->fetch()) !== null) {
			
			$csv->write_line($t);
		}
	    
		// データ出力
		clean_output_shutdown(array(
			"download" =>basename($csv_filename),
			"file" =>$csv_filename,
		));
	}
	
<? endif; /* $c["usage"] != "form" && $c["use_csv"] */ ?>
<? if($c["usage"] != "view" && $c["use_csv"]): /* ------------------- act_csv_entry ------------------ */ ?>
	//-------------------------------------
	// Action: CSVアップロードフォーム
	public function act_entry_csv_form () {
		
		$this->context("c",1,true);
	}

	//-------------------------------------
	// Action: CSVアップロード確認
	public function act_entry_csv_confirm () {
		
		$this->context("c",1,true);
		$this->c->input($_REQUEST["c"]);

		$csv_filename =obj("UserFileManager")->get_uploaded_file(
				$this->c->input("Import.csv_file"), "private");
		
		if ( ! $csv_filename) {

			redirect("page:.entry_csv_form");
		}

		redirect('page:.entry_csv_exec');
	}

	//-------------------------------------
	// Action: CSV一括インポート実行
	public function act_entry_csv_exec () {
		
		set_time_limit(0);
		registry("Report.error_reporting",E_USER_ERROR|E_ERROR);
		
		$this->context("c",1,true);
		
		$csv_filename =obj("UserFileManager")->get_uploaded_file(
				$this->c->input("Import.csv_file"), "private");
		
		// CSVファイルの読み込み準備
		$csv =new CSVHandler($csv_filename,"r",$this->csv_setting);

		dbi()->begin();

		while (($t=$csv->read_line()) !== null) {

			// CSVフォーマットエラー
			if ($errors =$csv->get_errors()) {
			
				dbi()->rollback();

				$this->c->errors("Import.csv_file",$errors);

				redirect("page:.entry_csv_form");
			}

			// DBへの登録
			$keys =array_keys($this->csv_setting["rows"]);
			$fields =$c_import->get_fields($keys);
			
			<?=_model_instance($t,$c)?>->save($fields,$c_import->id());
		}

		dbi()->commit();

		redirect("page:.view_list");
	}
<? endif; /* $c["usage"] != "view" && $c["use_csv"] */ ?>
}