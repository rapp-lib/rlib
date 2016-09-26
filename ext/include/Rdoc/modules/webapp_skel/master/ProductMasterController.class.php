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

/**
 * @controller
 */
class <?=str_camelize($c["name"])?>Controller extends Controller_App 
{
<? if ($c["usage"] != "form"): /* ------------------- list_setting ------------------ */ ?>
	/**
	 * 検索フォーム設定
	 */
	protected $list_setting =array(
		"search" =>array(
<? foreach ($this->filter_fields($t["fields"],"search") as $tc): ?>
			"<?=$tc['short_name']?>" =>array(
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
	/**
	 * CSV設定
	 */
	protected $csv_setting = array(
		"file_charset" =>"SJIS-WIN",
		"data_charset" =>"UTF-8",
		"rows" =>array(
			"<?=$t['pkey']?>" =>"#ID",
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
			"<?=$tc['short_name']?>" =>"<?=$tc['label']?>",
<? endforeach; ?>
		),
		"filters" =>array(
			array("filter" =>"sanitize"),
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
<? if ($tc['list']): ?>
			array("target" =>"<?=$tc['short_name']?>",
					"filter" =>"list_select", 
<? if ($tc['type'] == "checklist"): ?>
					"delim" =>"/", 
<? endif; /* $tc['type'] == "checklist" */ ?>
					"list" =>"<?=$tc['list']?>"),
<? endif; /* $tc['list'] */ ?>
<? if ($tc['type'] == "date"): ?>
			array("target" =>"<?=$tc['short_name']?>",
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
	/**
	 * @page
	 * @title <?=$c["label"]?> TOP
	 */
	public function act_index () 
	{
<? if ($c["usage"] == "form"): ?>
		redirect("page:.entry_form");
<? else: ?>
		redirect("page:.view_list");
<? endif; ?>
	}
	
<? if ($c["usage"] != "form"): /* ------------------- act_view_* ------------------ */ ?>
	/**
	 * @page
	 * @title <?=$c["label"]?> 一覧表示
	 */
	public function act_view_list () 
	{
		$this->context("c",1);

		if ($_REQUEST["_i"]=="c") {
			$this->c->clear();
			$this->c->input($_REQUEST);
        }
        
		list($this->vars["ts"] ,$this->vars["p"]) =
			<?=_model_instance($t,$c)?>->get_by_search_form($this->list_setting, $this->c->input());
	}

<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): /* ------------------- act_entry_* ------------------ */ ?>
	/**
	 * @page
	 * @title <?=$c["label"]?> 入力フォーム
	 */
	public function act_entry_form () 
	{
		$this->context("c",1,true);

		// 入力値のチェック
		if ($_REQUEST["_i"]=="c") {
			$this->c->validate_input($_REQUEST,array(
			));

			if ($this->c->has_valid_input()) {
				redirect("page:.entry_confirm");
			}
		}
		
		// id指定があれば既存のデータを読み込む
		if ($_REQUEST["id"]) {
			$this->c->id($_REQUEST["id"]);
			$t =<?=_model_instance($t,$c)?>->get_by_id($this->c->id());
			
			if ( ! $t) {
				$this->c->id(false);
				redirect("page:.view_list");
			}
			
			$this->c->input($t);
		}
	}

	/**
	 * @page
	 * @title <?=$c["label"]?> 確認
	 */
	public function act_entry_confirm () 
	{
		$this->context("c",1,true);
		$this->vars["t"] =$this->c->get_valid_input();
<? if ($c["usage"] != "form"): ?>

		redirect("page:.entry_exec");
<? endif; ?>
	}

	/**
	 * @page
	 * @title <?=$c["label"]?> 完了
	 */
	public function act_entry_exec () 
	{
		$this->context("c",1,true);
		if ($this->c->has_valid_input()) {
<? if ($t["virtual"]): ?>
			// メールの送信
			$this->send_mail(array(
				"template" =>"sample",
				"vars" =>array("t" =>$this->c->get_valid_input()),
			));
<? else: /* $t["virtual"] */ ?>
			// データの記録
			$fields =$this->c->get_fields(array(
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
				"<?=$tc['short_name']?>",
<? endforeach; ?>
			));
			<?=_model_instance($t,$c)?>->save($fields,$this->c->id());
<? endif; /* $t["virtual"] */ ?>
			
			$this->c->clear();
		}
<? if ($c["usage"] != "form"): ?>

		redirect("page:.view_list");
<? endif; ?>
	}
	
<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] == ""): /* ------------------- act_delete_* ------------------ */ ?>
	/**
	 * @page
	 * @title <?=$c["label"]?> 削除
	 */
	public function act_delete () 
	{
		$this->context("c");
		
		// idの指定
		$this->c->id($_REQUEST["id"]);
		
		// 既存のデータを確認
		$t =<?=_model_instance($t,$c)?>->get_by_id($this->c->id());
		
		if ( ! $t) {
			redirect("page:.view_list");
		}
		
		// データの削除
		<?=_model_instance($t,$c)?>->drop($this->c->id());
		
		redirect("page:.view_list");
	}

<? endif; /* $c["usage"] == "" */ ?>
<? if($c["usage"] != "form" && $c["use_csv"]): /* ------------------- act_view_csv ------------------ */ ?>
	/**
	 * @page
	 * @title <?=$c["label"]?> CSVダウンロード
	 */
	public function act_view_csv () 
	{
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
	/**
	 * @page
	 * @title CSVインポートフォーム
	 */
	public function act_entry_csv_form () 
	{
		$this->context("c",1,true);

		// 入力値のチェック
		if ($_REQUEST["_i"]=="c") {
			$this->c->validate_input($_REQUEST,array(
				"csv_file",
			));

			if ($this->c->has_valid_input()) {
				redirect("page:.entry_csv_confirm");
			}
		}
	}

	/**
	 * @page
	 * @title CSVインポート確認
	 */
	public function act_entry_csv_confirm () 
	{
		$this->context("c",1,true);
		$this->vars["t"] =$this->c->get_valid_input();

		redirect('page:.entry_csv_exec');
	}

	/**
	 * @page
	 * @title CSVインポート完了
	 */
	public function act_entry_csv_exec () 
	{
		set_time_limit(0);
		registry("Report.error_reporting",E_USER_ERROR|E_ERROR);
		
		$this->context("c",1,true);
		
		$csv_filename =obj("UserFileManager")->get_uploaded_file(
				$this->c->input("csv_file"), "private");
		
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
			$c_import =new Context_App;
			$c_import->id($t["<?=$t['pkey']?>"]);
			$c_import->input($t);
			
			$keys =array_keys($this->csv_setting["rows"]);
			$fields =$c_import->get_fields($keys);
			
			<?=_model_instance($t,$c)?>->save($fields,$c_import->id());
		}

		dbi()->commit();

		redirect("page:.view_list");
	}
<? endif; /* $c["usage"] != "view" && $c["use_csv"] */ ?>
}