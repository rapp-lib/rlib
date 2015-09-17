<?php
	
	//-------------------------------------
	// 
	function readme_datastate ($options, $webapp_build_testcase) {

		ob_start();
?>
README datastate(DBデータ管理)
	v1.0 110629 Y.Toyosawa

--------------------------------------------------------------------------
[目次]
	
	[DataState.1]DBダンプの作成
	
	[DataState.2]DBダンプのリストア
		
	[DataState.3]CSVによるDBデータ書き出し

	[DataState.4]CSVによるDBデータ投入
	
-------------------------------------
[DataState.1]DBダンプの作成

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="get" target="_blank">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][datastate]"/>
<input type="hidden" value="1" name="_[webapp_build][create_dump]"/>
接続： <select name="_[webapp_build][connection]">
<? foreach (registry("DBI.connection") as $name => $info): ?>
	<option value="<?=$name?>"><?=$name?></option>
<? endforeach; ?>
</select>

<input type="submit" value="DBダンプデータの作成"/>
</form>

-------------------------------------
[DataState.2]DBダンプのリストア

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="post" target="_blank" enctype="multipart/form-data" onsubmit="return confirm('既存データは破壊されます');">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][datastate]"/>
<input type="hidden" value="1" name="_[webapp_build][restore_dump]"/>
接続： <select name="_[webapp_build][connection]">
<? foreach (registry("DBI.connection") as $name => $info): ?>
	<option value="<?=$name?>"><?=$name?></option>
<? endforeach; ?>
</select>

Dbダンプファイル：<input type="file" name="dump_sql"/>

<input type="submit" value="DBダンプファイルのリストア"/>
</form>

-------------------------------------
[DataState.3]CSVによるDBデータ書き出し

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="post" target="_blank" enctype="multipart/form-data">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][datastate]"/>
<input type="hidden" value="1" name="_[webapp_build][create_ds]"/>
接続： <select name="_[webapp_build][connection]">
<? foreach (registry("DBI.connection") as $name => $info): ?>
	<option value="<?=$name?>"><?=$name?></option>
<? endforeach; ?>
</select>

<input type="submit" value="CSVデータ書き出し実行"/>
</form>

-------------------------------------
[DataState.4]CSVによるDBデータ投入

<form action="<?=file_to_url(registry("Path.html_dir"))?>" method="post" target="_blank" enctype="multipart/form-data" onsubmit="return confirm('既存データは破壊されます');">
<input type="hidden" value="1" name="exec"/>
<input type="hidden" value="1" name="_[webapp_build][datastate]"/>
<input type="hidden" value="1" name="_[webapp_build][restore_ds]"/>
接続： <select name="_[webapp_build][connection]">
<? foreach (registry("DBI.connection") as $name => $info): ?>
	<option value="<?=$name?>"><?=$name?></option>
<? endforeach; ?>
</select>

CSVファイル：<input type="file" name="ds_csv"/>

<input type="submit" value="CSVデータ投入実行"/>
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