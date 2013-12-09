<?php

	function input_type_file ($params, $preset_value, $postset_value, $smarty) {
		
		// Attrsの組み立て
		$op_keys =array(
			"type",
			"name",
			"value",
			"group",
			"assign", // 部品をアサインするテンプレート変数名
		);
		$attr_html ="";
		
		foreach ($params as $key => $value) {
			
			if ( ! in_array($key,$op_keys)) {
			
				$attr_html .=' '.$key.'="'.$value.'"';
			}
		}
		
		$value =$postset_value
				? $postset_value
				: $preset_value;
		$file =obj("UserFileManager")->get_filename($value,$group);
		$url =file_to_url($file);
		
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["elm_id"] ='ELM_'.$html["alias"];
		
		// LRA共通ヘッダ／フッタ
		$html["head"] ='<span id="area_'.$html["elm_id"].'">'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="file"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][group]"'
				.' value="'.$params["group"].'"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>'
				.'<input type="hidden"'
				.' name="_LRA['.$html["alias"].'][files_key]"'
				.' value="'.$html["alias"].'"/>'
				.'<input type="hidden" id="input_'.$html["elm_id"].'"'
				.' name="'.$params["name"].'"'
				.' value="'.$value.'"/>';
		$html["foot"] ='</span>';
		
		// アップロード済み領域の削除JS
		$html["delete_js"] ='if (document.getElementById(\'link_set_'.$html["elm_id"].'\')) {'
				.' document.getElementById(\'input_'.$html["elm_id"].'\').value=\'\';'
				.' document.getElementById(\'link_set_'.$html["elm_id"].'\').style.display=\'none\';'
				.' } return false;';
		
		// アップロード済み領域
		$html["link"] =$file ? '<a href="'.$url.'" target="_blank">アップロード済み</a>' : '';
		$html["delete"] =$file ? '<a href="javascript:void(0)"'
				.' onclick="'.$html["delete_js"].'">(削除)</a>' : '';
		$html["link_set"] ='<span id="link_set_'.$html["elm_id"].'">'
				.' '.$html["link"]
				.' '.$html["delete"]
				.'</span>';
		
		// fileコントロール
		$html["upload"] ='<input type="file" name="'.$html["alias"].'"'
				.' onchange="'.$html["delete_js"].'"'.$attr_html.' />';
		
		// HTML一式
		$html["full"] =$html["head"].$html["upload"].$html["link_set"].$html["foot"];
			
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$ref =& ref_array($template->_tpl_vars,$params["assign"]);
			$ref =$html;

			return null;
		}
		
		return $html["full"];
	}