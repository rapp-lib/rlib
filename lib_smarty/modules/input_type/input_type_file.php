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
		$url =obj("UserFileManager")->get_url($value,$params["group"]);
		
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["elm_id"] ='ELM_'.$html["alias"];
		
		// LRA共通ヘッダ／フッタ
		$html["head"] ='<span class="vifUploadContainer">'
				.'<input type="hidden" class="lraName"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden" class="lraMode"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="file"/>'
				.'<input type="hidden" class="lraGroup"'
				.' name="_LRA['.$html["alias"].'][group]"'
				.' value="'.$params["group"].'"/>'
				.'<input type="hidden" class="lraVarName"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>'
				.'<input type="hidden" class="lraFiles_key"'
				.' name="_LRA['.$html["alias"].'][files_key]"'
				.' value="'.$html["alias"].'"/>'
				.'<input type="hidden" class="lraResponse"'
				.' name="_LRA['.$html["alias"].'][response]"'
				.' value=""/>'
				.'<input type="hidden" id="value_'.$html["elm_id"].'" class="lraValue"'
				.' name="'.$params["name"].'"'
				.' value="'.$value.'"/>';
		$html["foot"] ='</span>';
		
		// アップロード済み領域の削除JS
		$html["delete_js"] ='if (document.getElementById(\'uploaded_set_'.$html["elm_id"].'\')) {'
				.' document.getElementById(\'value_'.$html["elm_id"].'\').value=\'\';'
				.' document.getElementById(\'uploaded_set_'.$html["elm_id"].'\').style.display=\'none\';'
				.' } return false;';
		
		// アップロード済み領域
		$html["uploaded_set_head"] ='<span id="uploaded_set_'.$html["elm_id"].'" class="uploadedSet"'
				.($value ? '' : ' style="display:none"').'>';
		$html["uploaded_set_foot"] ='</span>';
		$html["uploaded_link"] =$url ? '<a href="'.$url.'" target="_blank" class="uploadedFile">アップロード済み</a>' : "アップロード済み";
		$html["uploaded_img"] =$url ? '<a href="'.$url.'" target="_blank" class="uploadedFile">'
				.'<img src="'.$url.'" class="uploadedFile">'.'</a>' : "";
		$html["uploaded_delete"] ='<a href="javascript:void(0)"'
				.' onclick="'.$html["delete_js"].'" class="delete">[削除]</a>';
		
		$html["message_area"] ='<span class="messageArea"></span>';
		
		// fileコントロール
		$html["upload"] ='<input type="file" name="'.$html["alias"].'"'
				.' onchange="'.$html["delete_js"].'"'.$attr_html.' />';
		
		// HTML一式
		$html["full"] =$html["head"]
				.$html["upload"]
				.$html["message_area"]
				.$html["uploaded_set_head"]
				." ".$html["uploaded_link"]
				." ".$html["uploaded_delete"]
				.$html["uploaded_set_foot"]
				.$html["foot"];
			
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
			
			$ref =& ref_array($template->_tpl_vars,$params["assign"]);
			$ref =$html;

			return null;
		}
		
		return $html["full"];
	}