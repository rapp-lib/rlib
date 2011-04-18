<?php

	function input_type_file ($params, $preset_value, $postset_value, $smarty) {
		
		/*
			○paramsの指定
				name:
					name属性
				
				value:
					codeの指定
						single_file_uploadの場合: codeがそのまま値として格納
						multi_file_uploadの場合: codeの配列が格納
						multi_file_upload_complexの場合: codeを含む二重連想配列で格納
				
				assign:
					各要素をテンプレートに展開する変数名
				
				group:
					ファイルアップロードグループの指定
					
				template:
					テンプレートファイルのパスを指定する
					例:
						module:/file_upload/single_file_upload.html（デフォルト）
						module:/file_upload/multi_file_upload.html
						module:/file_upload/single_file_upload.html
				
				auto_extract:
					Valueを自動的にunserializeする
					
				complex:
					multi_file_uploadにおいてファイル付帯情報を扱えるようにする
					例: subname,date
		*/
		
		$code =$postset_value
				? $postset_value
				: $preset_value;
		$template =$params["template"]
				? $params["template"]
				: "module:file_upload/single_file_upload.html";
		$assign =$params["assign"];
		$name =$params["name"];
		$group =$params["group"];
		$auto_extract =$params["auto_extract"];
		
		if ($auto_extract && ! is_array($code)) {
			
			$code =@unserialize($code);
		}
		
		if (is_array($code)) {
		
			$url =array();
			
			foreach ($code as $index => $complex) {
			
				$code_spec =is_array($complex) ? $complex["code"] : $complex;
				$filename =obj("UserFileManager")->get_filename($code_spec,$group);
				$url[$index] =file_to_url($filename);
			}
			
		} else{
		
			$filename =obj("UserFileManager")->get_filename($code,$group);
			$url =file_to_url($filename);
		}
		
		$v["CODE"] =$code;
		$v["GROUP"] =$group;
		$v["ALIAS"] =sprintf("UFM%09d",mt_rand());
		$v["DATANAME"] =$name;
		$v["FILE"] =$url;
		$v["ATTRS"] =$params;
		
		$v["HTML"] =$smarty->fetch($template,null,null,false,array("v"=>$v));
		
		// テンプレート変数へのアサイン
		if ($assign) {
			
			$ref =& ref_array($template->_tpl_vars,$assign);
			$ref =$v;
		}
		
		return $v["HTML"];
	}