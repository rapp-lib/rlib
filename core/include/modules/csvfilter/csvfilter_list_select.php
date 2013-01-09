<?php
	
	//-------------------------------------
	// 
	function csvfilter_list_select ($value, $mode, $filter, $csv) {
		
		// 全要素は処理できない
		if (is_array($value)) {
			
			return $value;
		}
		
		// 空白要素の無視
		if ( ! strlen($value)) {
			
			return $mode=="r" ? null : "";
		}
		
		$list_options =get_list($filter["list"]);
		
		// CSV読み込み時
		if ($mode == "r") {
			
			$value =$list_options->select_reverse($value);
			
		// CSV書き込み時
		} elseif ($mode == "w") {
			
			$value =$list_options->select($value);
		}
		
		if ($value===null) {
			
			$csv->register_error("設定された値が不正です",true,$filter["target"]);
		}
		
		return $value;
	}