<?php
	
	/*
		パラメータ：
	 		※targetの指定は通常行わないこと
	*/
	//-------------------------------------
	// CSVからの入力項目についてのValidate
	function csvfilter_validate ($values, $mode, $line, $filter, $csv) {
		
		// オプションチェック
		if ($filter["target"]) {
			
			report_error('csvfilter:validate targetの指定は不可',array(
				"filter" =>$filter,
			));
		}
		
		// CSV読み込み時
		if ($mode == "r") {
			
			$c_import =new Context_App;
			$c_import->input($values);
			
			$c_import->validate((array)$filter["required"],(array)$filter["rules"]);
			
			// 入力チェック結果をCSVエラーに追加
			if ($c_import->errors()) {

				foreach ($c_import->errors() as $row => $message) {

					$csv->register_error($message,true,$row);
				}
			}
			
		// CSV書き込み時
		} elseif ($mode == "w") {
		}
		
		return $value;
	}