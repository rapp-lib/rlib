<?php

//-----------------------------------------------
// PHPでの定義情報を解析
class WebappBuilderScriptScanner extends WebappBuilder {
	
	/*
		.[class]
			.desc
			.file
			.line
			.extends
			.func.[name]
				.desc
				.file
				.line
				.arg
				.access
	*/
	protected $str =array();
	
	//-------------------------------------
	// PHPシステムの構造解析
	public function profile_system () {
		
		$root_dir =$this->options["target"];
		
		if ( ! $root_dir || $root_dir == "webapp") {
			
			$root_dir =registry("Path.webapp_dir");
		
		} elseif ($root_dir == "lib") {
			
			$root_dir =RLIB_ROOT_DIR;
		}
		
		$this->profile_dir($root_dir);
		
		print $this->get_catalog_html($root_dir);
	}
	
	//-------------------------------------
	// ディレクトリ内のPHPファイルの解析
	public function profile_dir ($root, $options=array()) {
		
		$files =obj("DirScanner")->scandir_recursive($root);
		
		$options["accept_pattern"] =$options["accept_pattern"] 
				? $options["accept_pattern"]
				: '!/[^/]+\.php$!';
		
		$options["except_pattern"] =$options["except_pattern"] 
				? $options["except_pattern"]
				: '!/\.[^/]+(/|$)!';
				
		foreach ($files as $file) {
		
			if ( ! preg_match($options["accept_pattern"],$file)
					|| preg_match($options["except_pattern"],$file)) {
				
				continue;
			}
			
			$this->profile_script($file);
		}
	}
	
	//-------------------------------------
	// 指定のPHPファイルの解析
	public function profile_script ($file) {
		
		$st_class ="GLOBALS";
		$st_pre_comment ="";
		$st_pre_arg ="";
		$st_comment ="";
		$st_func ="";
		
		foreach (file($file) as $line_index => $line) {
			
			// phpタグで開始しないファイルは対象外
			if ($line_index==0 && ! preg_match('!^<\?php!',$line)) {
				
				break;
			}
			
			// クラス定義コメント内容
			if ($st_pre_comment && preg_match('!^\s*///?(.*?)$!',$line,$match)) {
				
				if (strlen($st_comment)) {
					
					$st_comment .="\n";
				}
				
				$st_comment .=trim($match[1]);
				
			// クラス定義コメントの終了
			} else {
			
				$st_pre_comment =false;
			}
			
			// クラス定義コメントの開始
			if (preg_match('!^\s*///?---!',$line,$match)) {
				
				$st_comment ="";
				$st_pre_comment =true;
			}
			
			// クラス定義
			if (preg_match('!^\s*(abstract)?\s*class\s+(\S+)\s+(?:extends\s+(\S+))?!',$line,$match)) {
			
				$st_class =trim($match[2]);
				$this->str[$st_class]["desc"] =$st_comment;
				$st_comment ="";
				$this->str[$st_class]["file"] =$file;
				$this->str[$st_class]["line"] =$line_index;
				$this->str[$st_class]["abstract"] =(boolean)$match[1];
				$this->str[$st_class]["extends"] =trim($match[3]);
				
				if (preg_match('!\.class\.php$!',$file)) {
				
					$category =$st_class;
					$category =preg_replace('!_.*?$!','',$category);
					$category =($category);
					$category =array_pop(explode("_",str_underscore($category)));
					$this->str[$st_class]["category"] =$category;
				}
			}
			
			// 複数行にわたった引数定義
			if ($st_pre_arg && preg_match('!^(.*?)(\)\s*\{)?\s*$!',$line,$match)) {
					
				$this->str[$st_class]["func"][$st_func]["arg"] .=trim($match[1]);
				
				if ($match[2]) {
				
					$st_pre_arg =false;
				}
			}
			
			// 関数定義
			if (preg_match('!^\s*(public|protected|private)?\s+function\s+((?:&\s*)?[_a-zA-Z0-9]+)\s*\('
					.'(.*?)(\)\s*\{)?\s*$!',$line,$match)) {
					
				$st_func =trim($match[2]);
				$this->str[$st_class]["func"][$st_func]["access"] =trim($match[1]);
				$this->str[$st_class]["func"][$st_func]["file"] =$file;
				$this->str[$st_class]["func"][$st_func]["line"] =$line_index;
				$this->str[$st_class]["func"][$st_func]["desc"] =$st_comment;
				$st_comment ="";
				$this->str[$st_class]["func"][$st_func]["arg"] =trim($match[3]);
				
				if ( ! $match[4]) {
				
					$st_pre_arg =true;
				}
			}
		}
	}
	
	//-------------------------------------
	// fileを基準に整列
	protected function sort_by_file ($a,$b) {
		
		return strcmp($a["file"],$b["file"]);
	}
	
	//-------------------------------------
	// 解析結果を取得
	public function get_result () {
		
		$str =(array)$this->str;
		uasort($str,array($this,"sort_by_file"));
		
		return $str;
	}
	
	//-------------------------------------
	// 解析結果のクラス/関数一覧をHTMLで取得
	public function get_catalog_html ($root_dir="") {
		
		$str =$this->get_result();
		$str_globals =(array)$str["GLOBALS"]["func"];
		
		// クラス一覧の作成
		$last_dir ="";
		
		foreach ($str as $c_name => $c_info) {
			
			if ( ! preg_match('!\.class\.php$!',$c_info["file"])) {
				
				unset($str[$c_name]);
			}
			
			foreach ((array)$c_info["func"] as $f_name => $f_info) {
				
				if ($f_info["access"] == "protected" 
						|| $f_info["access"] == "private") {
						
					unset($str[$c_name]["func"][$fname]);
				}
			}
		}
		
		// 関数一覧作成
		$str_func =array();
		
		foreach ($str_globals as $func_name => $func_info) {
		
			$file_name =preg_replace('!^'.preg_quote($root_dir).'!','',$func_info["file"]);
			$str_func[$file_name][$func_name] =$func_info;
		}
	
		ob_start();
		
?>	
<html>
<head>
<title>Class/Function Catalog</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body style="background-color:#eeeeee;">

<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;">
	<h2>Class Catalog</h2>
	<? foreach ($str as $class_name => $class_info): ?>
		<? $cur_dir =dirname($class_info["file"]); ?>
		<? $same_dir =$last_dir==$cur_dir; ?>
		<? $last_dir =$cur_dir; ?>
		<? if ( ! $same_dir): ?>
			<br/>&nbsp;<?=preg_replace('!^'.preg_quote($root_dir).'!','',$cur_dir)?><br/>
		<? endif; ?>
			&nbsp;&nbsp;&nbsp;<a href="#<?=$class_name?>"><?=$class_name?></a><br/>
	<? endforeach; ?>
</div>

<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;">
	<h2>Function Catalog</h2>
	<? foreach ($str_func as $file_name => $func_list): ?>
		<br/>&nbsp;<?=preg_replace('!^'.preg_quote($root_dir).'!','',$file_name)?><br/>
		<? foreach ($func_list as $func_name => $func_info): ?>
			&nbsp;&nbsp;&nbsp;<a href="#<?=$func_name?>"><?=$func_name?></a><br/>
		<? endforeach; ?>
	<? endforeach; ?>
</div>

<? foreach ($str as $class_name => $class_info): ?>
	<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;<?
			?><? if ($class_info["abstract"]): ?> background-color: #eeeeee;<? endif; ?>">
	<a name="<?=$class_name?>"></a>
	<font color="#00aa00"> // <?=$class_info["desc"]?></font><br/>
	<b><font color="#0000aa"> <?=$class_name?></font></b> 
	(<?=preg_replace('!^'.preg_quote($root_dir).'!','',dirname($class_info["file"]))?>)<br/>
	<? if ($class_info["extends"]): ?>
		&nbsp;&nbsp;<font color="#888888">extends <b><?=$class_info["extends"]?></b></font><br/>
	<? endif; ?>
	<br/>
	<? foreach ((array)$class_info["func"] as $func_name => $func_info): ?>
		<font color="#00aa00"> // <?=$func_info["desc"]?> [L<?=$func_info["line"]?>]</font><br/>
		&nbsp;&nbsp;<b><font color="#aa0000"><?=$func_name?></font></b> (<?=$func_info["arg"]?>)<br/>
		<br/>
	<? endforeach; ?>	
	</div>
<? endforeach; ?>

<? foreach ($str_func as $file_name => $func_list): ?>
	<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;">
	<b><font color="#0000aa"> <?=$file_name?></font></b><br/>
	<br/>
	<? foreach ($func_list as $func_name => $func_info): ?>
		<a name="<?=$func_name?>"></a><br/>
		<font color="#00aa00"> // <?=$func_info["desc"]?> [L<?=$func_info["line"]?>]</font><br/>
		&nbsp;&nbsp;<b><font color="#aa0000"><?=$func_name?></font></b> (<?=$func_info["arg"]?>)<br/>
		<br/>
	<? endforeach; ?>	
	</div>
<? endforeach; ?>
</body>
</html>
<?

		return ob_get_clean();
	}
}