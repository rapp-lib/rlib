<?php

	function smarty_function_input ($params, $smarty) {
		
		$type =& $params["type"];
		$name =& $params["name"];
		
		// name属性補完
		if ( ! strlen($params['name']) && ($type=='submit' || $type=='button' 
				|| $type=='image' || $type=='reset')) {
				
			$name ='button';
		}
		
		// 必須属性チェック
		if ( ! isset($type)) {
		
			report_warning("Input type is unspecified.");
			return "";
			
		} elseif ( ! isset($params['name'])) {
		
			report_warning("Input name is unspecified.");
			return "";
		}
		
		// []の解決
		if (strpos($name,"[]")!==false) {
		
			$input_index_key =preg_replace('!\[\].*$!','',$name);
			$input_index_value =++$smarty->_input_index[$input_index_key];
			
			$name =str_replace('[]','['.$input_index_value.']',$name);
		}
		
		// フォームの値
		$name_ref =$name;
		$name_ref =str_replace('][','.',$name_ref);
		$name_ref =str_replace('[','.',$name_ref);
		$name_ref =str_replace(']','',$name_ref);
		
		$postset_value =ref_array($smarty->_tpl_vars,"input.".$name_ref);
		
		// Contextのinput内のデータ
		if ( ! $postset_value
				&& preg_match('!^([^\[]+)\[([^\[]+)\]((\[[^\[]+\])*)$!',$name,$match)) {
			
			$context_name =$match[1];
			$name_ref_context =$match[2];
			$name_ref_complex =$match[3];
			
			if ($context =$smarty->_tpl_vars[$context_name]) {
			
				$postset_value =$context->input($name_ref_context);
				
				// input要素内が配列構造
				if ($name_ref_complex) {
					
					// Serializeサれた文字列は展開
					if (is_string($postset_value)) {
						
						$postset_value =unserialize($postset_value);
					}
					
					// 参照可能であれば、下層のデータを抽出
					if ($postset_value && is_array($postset_value)) {
					
						$name_ref_complex =str_replace('][','.',$name_ref_complex);
						$name_ref_complex =str_replace('[','',$name_ref_complex);
						$name_ref_complex =str_replace(']','',$name_ref_complex);
						
						$postset_value =ref_array($postset_value,$name_ref_complex);
					
					} else {
					
						$postset_value =null;
					}
				}
			}
		}

		// HTML上で指定したvalue属性
		$preset_value =$params['value'];
		unset($params['value']);
		
		// 各typeの実装を呼び出す
		$module =load_module("input_type",$type,true);
		return $module($params,$preset_value,$postset_value,$smarty);
	}
	