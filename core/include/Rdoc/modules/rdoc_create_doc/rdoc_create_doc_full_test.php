<?php 
	

	
	//-------------------------------------
	// 結合テスト仕様書生成
	function rdoc_create_doc_full_test () {
		
		// テストから除外するテスト項目
		$needless_list =array(
			"1" =>"ID",
			"2" =>"登録日時",
			"3" =>"最終更新日時",
			"4" =>"削除フラグ",
		);

		$map =array(
			"wrapper",
			"page_group_name",
			"page_name",
			"test_group_item",
			"test_item",
			"type",
			"type_condition",
			"operation",
			"expectation_results",
			"etc",
			"error_check",
			"error_message",
			"screen_transition",
			"test_date",
			"test_results",
			"test_etc",
		);
		$data =array();
		
		// データの構築
		$old_wrapper =""; // メモ：重複制御
		foreach (registry("Schema.controller") as $controller_name => $_controller) {
			
			if ($old_wrapper != $_controller["wrapper"]) {
			
				$data[] =array(
					"wrapper" =>$_controller["wrapper"],
				);
			}
			$old_wrapper =$_controller["wrapper"];
			
			$data[] =array(
				"page_group_name" =>$_controller["label"],
			);
			foreach (registry("Schema.page.".$controller_name) as $action_name => $_page) {
				
				$page_name =$controller_name.".".$action_name;
				$page_type =$_controller["type"].".".$_page["type"];
				$_table =registry("Schema.tables.".$_controller["table"]);
				$_cols =registry("Schema.cols.".$_controller["table"]);
				
				$data[] =array(
					"page_name" =>$_page["label"],
				);
								
				if ($page_type=="master.entry_form") {
					
					$line_count = count($data); // メモ:ライン調整
					foreach ($_cols as $col_name => $_col) {
						
						if (in_array($_col["label"],$needless_list)) { continue; }
						
						$data[$line_count] =array(
							"test_item" =>$_col["label"],
							"type" =>"入力系",
							"expectation_results" =>"入力できる"
						);
						// required
						if (array_key_exists("required" , $_col)) {
							
							$line_count++;
							$data[$line_count] =array("type" =>"エラー系");
							$data[$line_count] +=array("type_condition" =>"未入力");
							$data[$line_count] +=array("operation" =>"送信");
							$data[$line_count] +=array("expectation_results" =>"エラーが表示される");
							$data[$line_count] +=array("error_check" =>"必須");
							$data[$line_count] +=array("error_message" =>"必須項目入力してください。");
						}
						// rules
						if (array_key_exists("rules" , $_col)) {
							
							if (array_key_exists("error_check",$data[$line_count])) {
								
								$line_count++;
								$data[$line_count] =array("type" =>"エラー系");
								$data[$line_count] +=array("type_condition" =>"入力にエラーがある");
								$data[$line_count] +=array("operation" =>"送信");
								$data[$line_count] +=array("expectation_results" =>"エラーが表示される");
								$data[$line_count] +=array("error_check" =>$_col["rules"]);
								$data[$line_count] +=array("error_message" =>$_col["rules"]."してください");
							}
						}						
						$line_count++;
					}

					// form固定操作系
					$data[] =array(
						"test_item" =>"登録・編集",
						"type" =>"操作系",
						"type_condition" =>"入力エラーがない",
						"operation" =>"クリック",
						"expectation_results" =>"画面遷移する",
						"screen_transition" =>"一覧画面"
					);
					
				}elseif ($page_type == "login.entry_form") {
				
				}
			}
		}

		return array($map,$data);
	}