<?php
	
	/*
		補足：
			formatを指定しないと評価のみで整形は行わない
		パラメータ：
	 		* target: 対象の項目
	 		format: longdate_formatに渡して整形を行う場合指定
	*/
	//-------------------------------------
	// 対象の値が正しい日付であるか評価、整形を行う
	function csvfilter_date ($value, $mode, $line, $filter, $csv) {
		
		// オプションチェック
		if ( ! $filter["target"]) {
			
			report_error('csvfilter:date targetの指定は必須です',array(
				"filter" =>$filter,
			));
			
			return $value;
		}
		
		// 空白要素の無視
		if ( ! strlen($value)) {
			
			return $mode=="r" ? null : "";
		}
		
		// CSV読み込み時
		if ($mode == "r") {
			
			if (strtotime($value) == -1) {
				
				$csv->register_error("設定された値が不正です",true,$filter["target"]);
				
				return null;
			}
			
			if ($filter["format"]) {
				
				$value =longdate_format($value,$filter["format"]);
			}
			
		// CSV書き込み時
		} elseif ($mode == "w") {
			
			if ( ! longdate($value)) {
				
				return "";
			}
			
			if ($filter["format"]) {
				
				$value =longdate_format($value,$filter["format"]);
			}
		}
		
		return $value;
	}