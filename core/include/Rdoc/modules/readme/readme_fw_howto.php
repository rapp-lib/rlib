<?php
	
	//-------------------------------------
	// 
	function readme_fw_howto ($options, $webapp_build_readme) {

		ob_start();
?>
README fw_howto
	v1.0 110406 Y.Toyosawa

--------------------------------------------------------------------------
[目次]

	[機能実装]
	
		[機能実装.1]ファイルアップロード
			
		[機能実装.2]検索/並べ替え/ページング
			
		[機能実装.3]CSVダウンロード

	[SQL組み立て]

		[SQL組み立て.1]基本のCRUD操作

		[SQL組み立て.2]

-------------------------------------
[機能実装.1]ファイルアップロード
	
HTML（"comment_master.entry_form.html"）:
Controller（"CommentMaster.class.php act_entry_form()"）:

-------------------------------------
[機能実装.2]検索/並べ替え/ページング

HTML（"comment_master.view_list.html"）:
Controller（"CommentMaster.class.php act_view_list()"）:

-------------------------------------
[機能実装.3]CSVダウンロード
			
Controller（"CommentMaster.class.php act_view_csv()"）:

			$csv_filename =create_file(registry("Path.tmp_dir")
					."/admin_export_eigyo_data/".rand_string().".csv");
			
			$csv =new CSVHandler($csv_filename,"w",array(
				"file_charset" =>"SJIS-WIN",
			));
			
			$csv->write_line($ts_label);
			$csv->write_lines($ts);
			
			clean_output_shutdown(array(
					"file"=>$csv_filename,
					"download"=>"eigyo_data.csv"));
					
<?
		$text =ob_get_clean();
		$text =str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",$text);
		$text =str_replace("\n","<br/>",$text);
		
		if (preg_match_all('!<br/>(?:-|\s)+<br/>(\[[^\]]+\])!',$text,$matches,PREG_SET_ORDER)) {
			
			foreach ($matches as $match) {
				
				$key =md5($match[1]);
				
				$text =preg_replace(
						'!'.preg_quote($match[0]).'!',
						'<br/><a href="#'.md5("[目次]").'">▲</a>'
							.'<a name="'.$key.'"></a>'."$0",
						$text);
				
				$text =preg_replace(
						'!'.preg_quote($match[1]).'!',
						'<a href="#'.$key.'">'."$0".'</a>',
						$text);
			}
		}
		
		return $text;
	}