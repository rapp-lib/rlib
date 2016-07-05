
	//-------------------------------------
	// Action: CSVダウンロード
	public function act_view_csv () {
		
		set_time_limit(0);
		registry("Report.error_reporting",E_USER_ERROR|E_ERROR);
	    
		$this->context("c",1);
	    
		$res =<?=$model_obj?> 
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