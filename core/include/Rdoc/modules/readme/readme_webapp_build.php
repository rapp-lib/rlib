<?php
	
	//-------------------------------------
	// 
	function readme_webapp_build ($options, $webapp_build_readme) {

		ob_start();
?>
README webapp_build
	v1.0 110329 Y.Toyosawa

--------------------------------------------------------------------------
[目次]
	
	[WebappBuilder.1]CSV/A5ER→Schema生成
		
	[WebappBuilder.2]画面生成
		
	[WebappBuilder.3]ロールバック

-------------------------------------
[WebappBuilder.1]CSV/A5ER→Schema生成

<? $f =file_exists(registry("Path.webapp_dir")."/config/schema.config.csv"); ?>
schema.config.csvがあるか ... <?=$f ? "YES" : "NO"?> 
<? $f =file_exists(registry("Path.webapp_dir")."/config/schema.config.a5er"); ?>
schema.config.a5erがあるか ... <?=$f ? "YES" : "NO"?> 
<? $f =file_exists(registry("Path.webapp_dir")."/config/schema.config.php"); ?>
schema.config.phpがあるか ... <?=$f ? "YES" : "NO"?> 
<? $f =registry("Schema"); ?>
Schema設定があるか ... <?=$f ? "YES" : "NO"?> 

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="get" target="_blank">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][schema]"/>
<select name="_[webapp_build][src]">
	<option value="csv">CSV→Schema</option>
	<option value="a5er">A5ER→CSV</option>
</select>
<input type="checkbox" value="1" name="_[webapp_build][force]"/>上書きを許可
<input type="submit" value="実行"/>
</form>

		
-------------------------------------
[WebappBuilder.2]画面生成

<? $f =file_exists(registry("Path.webapp_dir")."/config/schema.config.php"); ?>
schema.config.phpがあるか ... <?=$f ? "YES" : "NO"?> 
<? $f =registry("Schema"); ?>
Schema設定があるか ... <?=$f ? "YES" : "NO"?> 
<? $f =file_exists(registry("Path.webapp_dir")."/config/_install.sql"); ?>
_install.sqlがあるか ... <?=$f ? "YES" : "NO"?> 

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="get" target="_blank">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][deploy]"/>
<input type="checkbox" value="1" name="_[webapp_build][force]"/>上書きを許可
<input type="submit" value="実行"/>
</form>


-------------------------------------
[WebappBuilder.3]ロールバック

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="get" target="_blank">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][rollback]"/>
HistoryKey<input type="text" value="" name="_[webapp_build][history]"/>
<input type="checkbox" value="1" name="_[webapp_build][action][create]" checked="checked"/>作成
<input type="checkbox" value="1" name="_[webapp_build][action][backup]"/>削除
<input type="checkbox" value="1" name="_[webapp_build][force]"/>上書きを許可
<input type="submit" value="実行"/>
</form>

	
<?
		$text =ob_get_clean();
		$text =str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",$text);
		$text =preg_replace('!>\n!',"BREAK",$text);
		$text =preg_replace('!\n!',"<br/>",$text);
		$text =preg_replace('!BREAK!',">\n",$text);
		
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