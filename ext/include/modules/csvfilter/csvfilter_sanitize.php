<?php
	
	/*
		パラメータ：
	 		※targetの指定は通常行わないこと
	*/
	//-------------------------------------
	// CSVからの入力項目についてのサニタイズ処理
	function csvfilter_sanitize ($values, $mode, $line, $filter, $csv) {
		
		// オプションチェック
		if ($filter["target"]) {
			
			report_error('csvfilter:sanitize targetの指定は不可',array(
				"filter" =>$filter,
			));
		}
		
		// CSV読み込み時
		if ($mode == "r") {
			
			$values =sanitize($values);
			
		// CSV書き込み時
		} elseif ($mode == "w") {
			
			foreach ($values as $k => $v) {
				
				$values[$k] =str_replace(array("&amp;","&lt;","&gt;"),array("&","<",">"),$v);
			}
		}
		
		return $values;
	}