<?php
	
	function smarty_modifier_select () {
		
		$args =func_get_args();
		$key =array_shift($args);
		$list_name =array_shift($args);
		$params =$args;
		
		$list_options =get_list($list_name);
		
		// Serializeされた文字列であれば配列に変換する
		if (is_string($key) && $key_unserialized =unserialize($key)) {
			
			$key =$key_unserialized;
		}
		
		// KEY=>0/1形式の配列に適用
		if (is_array($key)) {
			
			$selected =array();
			
			foreach ($key as $k => $v) {
				
				if ($v) {
				
					$selected[$k] =$list_options->select($k,$params);
				}
			}
			
			return $selected;
			
		} else {
		
			return $list_options->select($key,$params);	
		}
	}
	