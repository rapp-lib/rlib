<?php
	
	//-------------------------------------
	// 
	function readme_code_viewer ($options, $webapp_build_readme) {

		ob_start();
		
		$file =$options["file"];
		if ($file=="//webapp") { $file =registry("Path.webapp_dir"); }
		if ($file=="//lib" || ! $file) { $file =RLIB_ROOT_DIR; }
		$file =realpath($file);
?>

<?_prepend_html()?>
<? if (is_file($file)): ?>
	<h3>File: <?=$file?></h3>
	<div id="code_box"><?_show_code($file)?></div>
<? elseif (is_dir($file)): ?>
	<h3>Dir: <?=$file?></h3>
	<div id="dir_tree"><?_dir($file)?></div>
<? endif; ?>

<?
		$text =ob_get_clean();
		
		$text ='<html><body>'.$text.'</body></html>';
		
		return $text;
	}
	
	//-------------------------------------
	//
	function _show_code ($file) {
		
		$code =file_get_contents($file);
		echo _highlight($code,$file);
	}
	
	//-------------------------------------
	//
	function _file_to_show_url ($file) {
		
		$url =url(file_to_url(registry("Path.html_dir")),array(
			"exec" =>"1",
			"_[webapp_build][readme]" =>"1",
			"_[webapp_build][page]" =>"code_viewer",
			"_[webapp_build][file]" =>$file,
		),"0001");
			
		return $url;
	}
	
	//-------------------------------------
	// ツリー展開
	function _dir ($path) {
		$path =$path."/";
		if ($handle = opendir($path)) {
			?><ul><?
			$queue = array();
			while (false !== ($file = readdir($handle))) {
				if (is_dir($path.$file) && $file != '.' && $file !='..') {
					_subdir($file,$path,$queue);
				} elseif ($file != '.' && $file !='..') {
					$queue[] = $file;
				}
			}
			_queue($queue, $path);
			?></ul><?
		}
	}
	function _queue ($queue, $path) {
		foreach ($queue as $file) { _file($file, $path); }
	}
	function _file ($file, $path) {
		?><li><a href="<?=_file_to_show_url($path.$file)?>" target="_blank"><?=$file?></a></li><?
	}
	function _subdir ($dir, $path) {
		?><li><span class="dir"><?=$dir?></span><?_dir($path.$dir."/")?></li><?
	}
	
	//-------------------------------------
	//
	function _prepend_html () {
?>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script type="text/javascript">
$(function() {
	$("span.dir").css("cursor", "pointer").prepend("+ ").click(function() {
		$(this).next().toggle("fast");
		var v = $(this).html().substring( 0, 1 );
		if ( v == "+" )
			$(this).html( "-" + $(this).html().substring( 1 ) );
		else if ( v == "-" )
			$(this).html( "+" + $(this).html().substring( 1 ) );
	}).next().hide();
    $("#dir_tree a, #dir_tree span.dir").hover(function() {
        $(this).css("font-weight", "bold");
    }, function() {
        $(this).css("font-weight", "normal");
    });
});
</script>
<style type="text/css">
body {font-family:Georgia, 'Times New Roman', Helvetica, Times, serif; color: #6a6a6a;}
#dir_tree ul {list-style-type: none; padding-left: 15px; _padding-left: 0px;}
#dir_tree a, #dir_tree li {text-decoration: none; margin-bottom: 3px;}
#dir_tree a {font-size:small; background-color:#f7f7f7; border-bottom:1px solid #f1f1f1; margin-left: 15px;}
#dir_tree,#code_box {border: 1px solid #dddddd; margin:10px; padding:10px; }
</style>
<?
	}
	
	function _highlight ($s,$file) {
			
		ini_set("highlight.comment","#007700"); // ソース内のコメント
		ini_set("highlight.html","#888888"); // PHPコード外のHTMLタグ
		ini_set("highlight.keyword","#0000ff"); // キーワード
		ini_set("highlight.string","#dd00dd"); // 文字列
		ini_set("highlight.default","#000066"); // ソース内のその他の文字
		
		$tag_color ="#0000ff";
		$html_bg_color ="#ddffdd";
		$smarty_bg_color ="#ffdddd";
		$comment_color ="#000000";
		$comment_bg_color ="#dddddd";
	
		// 整形
		if (preg_match('!\.(php|html|htm|sql|txt)$!',$file)) {
			
			$s =highlight_string($s,true);
			
			// XML
			$s = preg_replace("#&lt;([^\s\?=\!])(.*)([\s]*?)&gt;#sU",
					"<span style=\"color:{$tag_color};background-color:{$html_bg_color};\">&lt;\\1\\2\\3&gt;</span>",$s);
			// Smarty
			$s = preg_replace("#{{([^\s\?=\*])(.*)([\[\s]|}})#iU",
					"<span style=\"color:{$tag_color};background-color:{$smarty_bg_color};\">{{\\1\\2\\3</span>",$s);
			// XMLコメント
			$s = preg_replace("#&lt;!--(.*)--&gt;#sU",
					"<span style=\"color:{$comment_color};background-color:{$comment_bg_color};\">\\0</span>",$s);
			// Smartyコメント
			$s = preg_replace("#\{\{\*(.*)\*\}\}#iU",
					"<span style=\"color:{$comment_color};background-color:{$comment_bg_color};\">\\0</span>",$s);
			
		} else {	
			
			$s ="<code>".nl2br($s)."</code>";
		}
		
		// 行番号
		$s =preg_replace('!(^<code>|^|<br />)!e','"$0"."<a name=\"L".(sprintf("%04d",++$i))."\"></a><b><span style=\"background-color:#dddddd;color:#888888;\">".str_replace(" ","&nbsp;",sprintf("%4d",$i))."</span></b> "',$s);
			
		return $s;
	}