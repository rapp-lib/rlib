

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
			$c_import =new Context_App;
			$c_import->id("<?=$t['pkey']?>");
			$c_import->input($t);
			
			$keys =array_keys($this->csv_setting["rows"]);
			$fields =$c_import->get_fields($keys);
			
			<?=$model_obj?>->save($fields,$c_import->id());
		}

		dbi()->commit();

		redirect("page:.view_list");
	}