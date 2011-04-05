<?php

//-------------------------------------
// CSVファイル入出力を補助するクラス
class CSVHandler {
	
	protected $filename;
	protected $mode;
	protected $handle;
	
	protected $delim;
	protected $escape;
	protected $file_charset;
	protected $data_charset;
	protected $return_code;
	protected $read_callback_by_line;
	protected $read_callback_by_cell;
	protected $write_callback_by_line;
	protected $write_callback_by_cell;
	protected $map;
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
		
		$this->delim =isset($options["delim"])
				? $options["delim"]
				: ",";
		$this->escape =isset($options["escape"])
				? $options["escape"]
				: '"';
		
		$this->file_charset =isset($options["file_charset"])
				? $options["file_charset"]
				: "UTF-8";
		$this->data_charset =isset($options["data_charset"])
				? $options["data_charset"]
				: "UTF-8";
		$this->return_code =isset($options["return_code"])
				? $options["return_code"]
				: "\n";
				
		$this->read_callback_by_line =isset($options["read_callback_by_line"])
				? $options["read_callback_by_line"]
				: null;
		$this->read_callback_by_cell =isset($options["read_callback_by_cell"])
				? $options["read_callback_by_cell"]
				: null;
				
		$this->write_callback_by_line =isset($options["write_callback_by_line"])
				? $options["write_callback_by_line"]
				: null;
		$this->write_callback_by_cell =isset($options["write_callback_by_cell"])
				? $options["write_callback_by_cell"]
				: null;
				
		$this->map =isset($options["map"])
				? $options["map"]
				: null;
				
		$this->ignore_empty_line =isset($options["ignore_empty_line"])
				? $options["ignore_empty_line"]
				: null;
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
	public function read_lines ($counter=null) {
		
		$lines =array();
		
		while ( ! is_null($line =$this->read_line())) {
			
			if ( ! is_null($counter) && $counter-->0) {
				
				break;
			}
			
			$lines[] =$line;
		}
		
		return $lines;
	}
	
	//-------------------------------------
	// 行ベースの読み込み
	public function read_line () {
		
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
			
			// セル単位コールバック
			if ( ! is_null($this->read_callback_by_cell)) {
				
				$csv_data[$i] =call_user_func(
						$this->read_callback_by_cell,$csv_data[$i]);
			}
		}
		
		// 行単位コールバック
		if ( ! is_null($this->read_callback_by_line)) {
			
			$csv_data =call_user_func($this->read_callback_by_line,$csv_data);
		}
		
		// KVマッピング
		if (is_array($this->map)) {
			
			$csv_data_tmp =array();
			
			foreach ($this->map as $k => $v) {
				
				$csv_data_tmp[$v] =$csv_data[$k];
			}
			
			$csv_data =$csv_data_tmp;
		}
		
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
	public function write_lines ($lines) {
		
		if ( ! is_array($lines)) {
			
			return false;
		}
		
		foreach ($lines as $line) {
			
			$this->write_line($line);
		}
		
		return true;
	}
	
	//-------------------------------------
	// 行書き込み
	public function write_line (array $csv_data) {
		
		if ($this->ignore_empty_line &&  ! strlen(implode("",$csv_data))) {
			
			return false;
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
			
			// セル単位コールバック
			if ( ! is_null($this->write_callback_by_cell)) {
				
				$csv_data[$i] =call_user_func(
						$this->write_callback_by_cell,$csv_data[$i]);
			}
			
			$csv_data[$i] =$this->quote_csv_data($csv_data[$i]);
		}
		
		// 行単位コールバック
		if ( ! is_null($this->write_callback_by_line)) {
			
			$csv_data =call_user_func($this->write_callback_by_line,$csv_data);
		}
		
		$csv_data =implode($this->delim,$csv_data).$this->return_code;
		
		return fwrite($this->handle,$csv_data);
	}
	
	//-------------------------------------
	// CSVセルエスケープ
	public function quote_csv_data ($value) {
			
		$value =(string)$value;
		$value =str_replace($this->escape,$this->escape.$this->escape, $value);
		
		$escape_pattern ='!['.preg_quote($this->escape.$this->delim.$this->return_code,"!").']!';
		
		if (preg_match($escape_pattern,$value)) {
		
			$value =$this->escape.$value.$this->escape;
		}
		
		return $value;
	}
}