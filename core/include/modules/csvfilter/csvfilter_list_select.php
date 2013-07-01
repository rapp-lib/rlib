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
		
		// listの指定
		$list_options =get_list($filter["list"]);
		
		// 複合データの場合
		if ($delim =$filter["delim"]) {
		
			// CSV読み込み時
			if ($mode == "r") {
				
				$value_exploded =explode($delim,$value);
				$value =array();
				
				foreach ($value_exploded as $k=>$v) {
					
					if ($v =$list_options->select_reverse($v)) {
						
						$value[$k] =$v;
					
					} else {
						
						$csv->register_error("設定された値が不正です",true,$filter["target"]);
					}
				}
				
			// CSV書き込み時
			} elseif ($mode == "w") {
			
				$value_unserialized =array();
				
				foreach ((array)unserialize($value) as $k=>$v) {
					
					if ($v =$list_options->select($v)) {
					
						$value_unserialized[$k] =$v;
						
					} else {
						
						$csv->register_error("設定された値が不正です",true,$filter["target"]);
					}
				}
				
				$value =implode($delim,$value_unserialized);
			}
		
		// 単純データの場合
		} else {
		
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
		}
		
		return $value;
	}