<?php
	
	//-------------------------------------
	// 
	function csvfilter_mi ($value, $mode, $filter, $csv) {
		
		// 個別要素は処理できない（＝target指定不可）
		if ( ! is_array($value)) {
			
			return $value;
		}
		
		$target_base =$filter["target_base"];

		// CSV読み込み時
		if ($mode == "r") {
		
			$value[$target_base] =array();
			
			foreach ($value as $k => $v) {
				
				// *.INDEX.ELM_NAMEのパターンに該当する要素を取り出す
				if (preg_match('!^'.preg_quote($target_base).'\.([^\.]+)\.(.*?)$!',$k,$match)) {
					
					list(,$mi_index,$mi_elm_name) =$match;
					
					$value[$target_base][$mi_index][$mi_elm_name] =$v;
					unset($value[$k]);
				}
			}
			
		// CSV書き込み時
		} elseif ($mode == "w") {
			
			$mi_value =(array)unserialize($value[$target_base]);
			unset($value[$target_base]);
			
			$mi_index_new =0;
			
			foreach ($mi_value as $mi_index => $mi_set) {
				
				foreach ($mi_set as $mi_elm_name => $v) {
					
					$value[$target_base.".".$mi_index_new.".".$mi_elm_name] =$v;
				}
				
				$mi_index_new++;
			}
		}
		
		return $value;
	}