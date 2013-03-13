<?php
	
	//-------------------------------------
	// 
	function csvfilter_date ($value, $mode, $filter, $csv) {
		
		// 全要素は処理できない
		if (is_array($value)) {
			
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