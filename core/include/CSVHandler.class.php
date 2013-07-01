<?php

//-------------------------------------
// CSVファイル入出力を補助するクラス
class CSVHandler {
	
	protected $current_line_num;
	protected $errors;
	
	protected $filename;
	protected $mode;
	protected $handle;
		
	protected $delim;
	protected $escape;
	protected $file_charset;
	protected $data_charset;
	protected $return_code;
	
	protected $map;
	protected $filters;
	protected $ignore_empty_line;
	
	//-------------------------------------
	// コンストラクタ
	public function __construct (
			$filename,
			$mode="r",
			$options=array()) {
		
		$this->handle =fopen($filename,$mode);
		
		$this->filename =$filename;
		$this->mode =$mode;
		
		$this->current_line_num =0;
		$this->errors =array();
		
		$this->delim =isset($options["delim"])
				? $options["delim"]
				: ",";
		$this->escape =isset($options["escape"])
				? $options["escape"]
				: '"';
		$this->return_code =isset($options["return_code"])
				? $options["return_code"]
				: "\n";
		
		$this->file_charset =isset($options["file_charset"])
				? $options["file_charset"]
				: "UTF-8";
		$this->data_charset =isset($options["data_charset"])
				? $options["data_charset"]
				: "UTF-8";
		
		$this->map =isset($options["map"])
				? $options["map"]
				: null;
				
		$this->filters =isset($options["filters"])
				? $options["filters"]
				: null;
				
		$this->ignore_empty_line =isset($options["ignore_empty_line"])
				? $options["ignore_empty_line"]
				: null;
	}
	
	//-------------------------------------
	// オプションの追加設定
	public function set_options ($options) {
		
		$overwritables =array("map","filters","ignore_empty_line");
		
		foreach ($options as $k => $v) {
			
			if (in_array($k,$overwritables)) {
			
				$this->$k =$v;
			}
		}
	}
	
	//-------------------------------------
	// ファイルハンドルの取得
	public function get_file_handle () {
		
		return $this->handle;
	}
	
	//-------------------------------------
	// 全データの読み込み
	public function read_all ($counter=null) {
		
		return $this->read_lines($counter);
	}
	
	//-------------------------------------
	// 複数の行読み込み
	public function read_lines ($options=array()) {
		
		$lines =array();
		$counter =$options["limit"];
		
		while ( ! is_null($line =$this->read_line($options))) {
			
			if ( ! is_null($counter) && $counter-->0) {
				
				break;
			}
			
			$lines[] =$line;
		}
		
		return $lines;
	}
	
	//-------------------------------------
	// 行ベースの読み込み
	public function read_line ($options=array()) {
		
		if ( ! $this->handle || feof($this->handle)) {
			
			if ($this->handle) {
				
				fclose($this->handle);
				$this->handle =null;
			}
			
			return null;
		}
		
		$line = "";
		$d =preg_quote($this->delim);
		$e =preg_quote($this->escape);
		
		do {
		
			$line .=fgets($this->handle);
			$item_count =preg_match_all('/'.$e.'/', $line, $dummy);
		
		} while ($item_count % 2 != 0);
		
		$csv_line =preg_replace('/(?:\r\n|[\r\n])?$/',$d,trim($line));
		$csv_pattern ='/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'
				.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
			
		preg_match_all($csv_pattern, $csv_line, $matches);
		
		$csv_data =$matches[1];
		
		for ($i=0; $i< count($csv_data); $i++) {
		
			$csv_data[$i] =preg_replace(
					'/^'.$e.'(.*)'.$e.'$/s','$1',
					$csv_data[$i]);
			$csv_data[$i] =str_replace($e.$e, $e, $csv_data[$i]);
			
			// エンコーディング変換
			if ($this->data_charset != $this->file_charset) {
				
				$csv_data[$i] =mb_convert_encoding(
						$csv_data[$i],
						$this->data_charset,
						$this->file_charset);
			}
		}
		
		// KVマッピング
		if (is_array($this->map)) {
			
			$csv_data_tmp =array();
			
			foreach ($this->map as $k => $v) {
				
				$csv_data_tmp[$v] =$csv_data[$k];
			}
			
			$csv_data =$csv_data_tmp;
		}
		
		// Filter実行
		if ($this->filters && ! $options["ignore_filters"]) {
		
			$filters =(array)$this->filters;
			ksort($filters);
			
			foreach ($filters as $filter) {
				
				$module =load_module("csvfilter",$filter["filter"],true);
				
				// target指定がある場合、要素単位で処理
				if ($filter["target"]) {
				
					$csv_data[$filter["target"]] =call_user_func_array($module,array(
						$csv_data[$filter["target"]],
						"r",
						$filter,
						$this,
					));
					
				// target指定がない場合、全要素を処理
				} else {
				
					$csv_data =call_user_func_array($module,array(
						$csv_data,
						"r",
						$filter,
						$this,
					));
					
					if ($csv_data === null) {
						
						return array();
					}
				}
			}
		}
		
		$this->current_line_num++;
		
		if (strlen(implode("",$csv_data))) {
			
			return $csv_data;
			
		} elseif ($this->ignore_empty_line) {
				
			return $this->read_line();
			
		} else {
			
			return array();
		}
	}
	
	//-------------------------------------
	// 複数行書き込み
	public function write_lines ($lines, $options=array()) {
		
		if ( ! is_array($lines)) {
			
			return false;
		}
		
		foreach ($lines as $line) {
			
			$this->write_line($line,$options);
		}
		
		return true;
	}
	
	//-------------------------------------
	// 行書き込み
	public function write_line (array $csv_data, $options=array()) {
		
		if ($this->ignore_empty_line &&  ! strlen(implode("",$csv_data))) {
			
			return false;
		}
		
		// Filter実行
		if ($this->filters && ! $options["ignore_filters"]) {
		
			$filters =(array)$this->filters;
			krsort($filters);
			
			foreach ($filters as $filter) {
				
				$module =load_module("csvfilter",$filter["filter"],true);
				
				// target指定がある場合、要素単位で処理
				if ($filter["target"]) {
				
					$csv_data[$filter["target"]] =call_user_func_array($module,array(
						$csv_data[$filter["target"]],
						"w",
						$filter,
						$this,
					));
					
				// target指定がない場合、全要素を処理
				} else {
				
					$csv_data =call_user_func_array($module,array(
						$csv_data,
						"w",
						$filter,
						$this,
					));
					
					if ($csv_data === null) {
						
						return;
					}
				}
			}
		}
		
		// VKマッピング
		if (is_array($this->map)) {
			
			$csv_data_tmp =array();
			
			foreach ($this->map as $k => $v) {
				
				$csv_data_tmp[$k] =$csv_data[$v];
			}
			
			$csv_data =$csv_data_tmp;
			
			ksort($csv_data);
		}
			
		$csv_data =array_values($csv_data);
		
		for ($i=0; $i< count($csv_data); $i++) {
		
			if ( ! isset($csv_data[$i])) {
				
				continue;
			}
			
			// エンコーディング変換
			if ($this->data_charset != $this->file_charset) {
				
				$csv_data[$i] =mb_convert_encoding(
						$csv_data[$i],
						$this->file_charset,
						$this->data_charset);
			}
			
			$csv_data[$i] =$this->quote_csv_data($csv_data[$i]);
		}
		
		$csv_data =implode($this->delim,$csv_data).$this->return_code;
		
		fwrite($this->handle,$csv_data);
		
		$this->current_line_num++;
	}
	
	//-------------------------------------
	// 次回または現在の読み込み/書き込みの行番号
	public function get_current_line_num () {
		
		return $this->current_line_num;
	}
	
	//-------------------------------------
	// エラーの登録
	public function register_error ($message, $line_num=null, $row=null) {
		
		$error =array();
		$error["message"] =$message;
		
		if ($line_num !== null) {
		
			$error["line"] =$line_num===true
					? $this->get_current_line_num()
					: $line_num;
		}
		
		if ($row !== null) {
		
			$error["row"] =$row;
		}
		
		$this->errors[] =$error;
	}
	
	//-------------------------------------
	// 読み込み/書き込みエラーの取得
	public function get_errors () {
		
		return $this->errors;
	}
	
	//-------------------------------------
	// CSVセルエスケープ
	protected function quote_csv_data ($value) {
			
		$value =(string)$value;
		$value =str_replace($this->escape,$this->escape.$this->escape, $value);
		
		$escape_pattern ='!['.preg_quote($this->escape.$this->delim.$this->return_code,"!").']!';
		
		if (preg_match($escape_pattern,$value)) {
		
			$value =$this->escape.$value.$this->escape;
		}
		
		return $value;
	}
}