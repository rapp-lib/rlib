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
		
		$this->root =$this->options["target"]
				? $this->options["target"]
				: registry("Path.webapp_dir");
		$catalog =$this->options["catalog"]
				? $this->options["catalog"]
				: "class";
		
		if ($this->root == "lib") {
			
			$this->root =RLIB_ROOT_DIR;
		}
		
		$this->profile_dir($this->root);
		
		if ($catalog == "class") {
		
			print $this->get_class_catalog();
		
		} elseif ($catalog == "function") {
		
			print $this->get_function_catalog();
		}
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
			if (preg_match('!^\s*(public|protected|private)?\s+function\s+(\S+)\s*\('
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
	// 解析結果の関数一覧をHTMLで取得
	public function get_function_catalog () {
		
		$str =$this->get_result();
		$str_globals =(array)$str["GLOBALS"]["func"];
		$str =array();
		$last_dir ="";
		
		foreach ($str_globals as $func_name => $func_info) {
		
			$file_name =preg_replace('!^'.preg_quote($this->root).'!','',$func_info["file"]);
			$str[$file_name][$func_name] =$func_info;
		}
	
		ob_start();
		
?>	
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body style="background-color:#eeeeee;">

<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;">
	Function Catalog<br/>
	<? foreach ($str as $file_name => $func_list): ?>
		<br/>&nbsp;<?=preg_replace('!^'.preg_quote($this->root).'!','',$file_name)?><br/>
		<? foreach ($func_list as $func_name => $func_info): ?>
			&nbsp;&nbsp;&nbsp;<a href="#<?=$func_name?>"><?=$func_name?></a><br/>
		<? endforeach; ?>
	<? endforeach; ?>
</div>


<? foreach ($str as $file_name => $func_list): ?>
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
	
	//-------------------------------------
	// 解析結果のClass一覧をHTMLで取得
	public function get_class_catalog () {
		
		$str =$this->get_result();
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
		
		ob_start();
		
?>	
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body style="background-color:#eeeeee;">

<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;">
	Class Catalog<br/>
	<? foreach ($str as $class_name => $class_info): ?>
		<? $cur_dir =dirname($class_info["file"]); ?>
		<? $same_dir =$last_dir==$cur_dir; ?>
		<? $last_dir =$cur_dir; ?>
		<? if ( ! $same_dir): ?>
			<br/>&nbsp;<?=preg_replace('!^'.preg_quote($this->root).'!','',$cur_dir)?><br/>
		<? endif; ?>
			&nbsp;&nbsp;&nbsp;<a href="#<?=$class_name?>"><?=$class_name?></a><br/>
	<? endforeach; ?>
</div>


<? foreach ($str as $class_name => $class_info): ?>
	<div style="background-color:#ffffff; border:1px solid #aaaaaa; margin:2px; padding:7px; margin:7px;<?
			?><? if ($class_info["abstract"]): ?> background-color: #eeeeee;<? endif; ?>">
	<a name="<?=$class_name?>"></a>
	<font color="#00aa00"> // <?=$class_info["desc"]?></font><br/>
	<b><font color="#0000aa"> <?=$class_name?></font></b> 
	(<?=preg_replace('!^'.preg_quote($this->root).'!','',dirname($class_info["file"]))?>)<br/>
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
</body>
</html>
<?

		return ob_get_clean();
	}
}