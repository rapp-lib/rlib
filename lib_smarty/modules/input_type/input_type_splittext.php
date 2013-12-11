<?php

	function input_type_splittext ($params, $preset_value, $postset_value, $smarty) {
	
		// 分割設定
		$settings =array(
			"tel" =>array("delim" =>"-", "length" =>3, "type" =>"text"),
			"zip" =>array("delim" =>"-", "length" =>2, "type" =>"text"),
			"mail" =>array("delim" =>"@", "length" =>2, "type" =>"text"),
		);
		
		if ( ! $params["mode"] && $settings[$params["mode"]]) {
		
			return 'error: mode attribute is-not valid.';
		}
		
		// Attrsの組み立て
		$op_keys =array(
			"type",
			"name",
			"value",
			"mode",
			"assign", // 部品をアサインするテンプレート変数名
		);
		$attrs =array();
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attrs[$key] =$value;
			}
		}
		
		// valueの分解/再構築
		$value =$postset_value
				? $postset_value
				: $preset_value;
		
		$html["elms"] =array();
			
		$html["setting"] =$settings[$params["mode"]];
		$values_splitted =explode($html["setting"]["delim"],$value,$html["setting"]["length"]);
		
		for ($i=0; $i<$html["setting"]["length"]; $i++) {
			
			$value_splitted =$values_splitted[$i];
		
			$attrs["type"] =$html["setting"]["type"];
			$attrs["name"] =$params["name"]."[".$i."]";
			$attrs["value"] =$value_splitted;
			$attrs["class"] =($attrs["class"] ? $attrs["class"]." " : "")."splittext_".$i;
			
			$html["elms"][$i] =tag("input",$attrs);
		}
		
		// LRA共通ヘッダ／フッタ
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["elm_id"] ='ELM_'.$html["alias"];
		$html["head"] ='<span class="splittextContainer">'
				.'<input type="hidden" class="lraName"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden" class="lraMode"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="splittext"/>'
				.'<input type="hidden" class="lraGroup"'
				.' name="_LRA['.$html["alias"].'][splitmode]"'
				.' value="'.$params["mode"].'"/>'
				.'<input type="hidden" class="lraVarName"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>';
		$html["foot"] ='</span>';
		
		// HTML一式
		$html["full"] =$html["head"]
				.implode(" ".$html["setting"]["delim"]." ",$html["elms"])
				.$html["foot"];
			
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$ref =& ref_array($template->_tpl_vars,$params["assign"]);
			$ref =$html;

			return null;
		}
		
		return $html["full"];
	}