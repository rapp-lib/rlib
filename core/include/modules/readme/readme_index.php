<?php
	
	//-------------------------------------
	// 
	function readme_index ($options, $webapp_build_readme) {

		ob_start();
?>
README index
	v1.0 110329 Y.Toyosawa

--------------------------------------------------------------------------

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][readme]=1&_[webapp_build][page]=about_lib" target="_blank">1.ドキュメント</a>

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][readme]=1&_[webapp_build][page]=webapp_build" target="_blank">2.自動生成支援</a>	
	
<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][profile]=1&_[webapp_build][target]=lib" target="_blank">3.ライブラリ内クラス/関数一覧</a>

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][profile]=1&_[webapp_build][target]=" target="_blank">4.Webapp内クラス/関数一覧</a>

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][readme]=1&_[webapp_build][page]=code_viewer&_[webapp_build][file]=//lib" target="_blank">5.ライブラリ内ファイル一覧</a>

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][readme]=1&_[webapp_build][page]=code_viewer&_[webapp_build][file]=//webapp" target="_blank">6.Webapp内ファイル一覧</a>

<a href="<?=file_to_url(registry("Path.html_dir"))?>?exec=1&_[webapp_build][readme]=1&_[webapp_build][page]=datastate" target="_blank">7.DBデータ管理</a>
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