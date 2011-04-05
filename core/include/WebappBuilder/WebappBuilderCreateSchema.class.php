<?php

//-------------------------------------
// 
class WebappBuilderCreateSchema extends WebappBuilder {
	
	//-------------------------------------
	// Schemaファイルを生成する
	public function create_schema () {
		
		report("HistoryKey: ".$this->history);
		
		$this->append_history(
				"memo",
				date("Y/m/d H:i"),
				$_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"]);
		
		$dest_file =registry("Path.webapp_dir")."/config/schema.config.php";
		$src_file_find_csv =registry("Path.webapp_dir")."/config/schema.config.csv";
		$src_file_find_a5er =registry("Path.webapp_dir")."/config/schema.config.a5er";
		
		if ($src_file =find_include_path($src_file_find_csv)) {
		
			$src =$this->load_schema_csv($src_file);
			return $this->deploy_src($dest_file,$src);
		
		} elseif ($src_file =find_include_path($src_file_find_a5er)) {
		
			$src =$this->load_schema_a5er($src_file);
			return $this->deploy_src($dest_file,$src);
		
		} else {
			
			report_warning("Schema-source-file is-not found.",array(
				"src_file_find_csv" =>$src_file_find_csv,
				"src_file_find_a5er" =>$src_file_find_a5er,
			));
			print "<pre>".htmlspecialchars($src)."</pre>";
		}
	}
	
	//-------------------------------------
	// A5ERファイルを読み込んでSchemaのコードを生成する
	protected function load_schema_a5er ($filename) {
		
		$st_cat ='';
		$st_table ="";
		$s =array();
		
		foreach (file($filename) as $line) {
			
			$line =trim($line);
			
			// 空行
			if ( ! $line) {
				
				continue;
			
			// カテゴリ表示行（[Entity]等）
			} elseif (preg_match('!^\[([^\]]+)\]$!',$line,$match)) {
				
				$st_cat =$match[1];
				continue;
			}
			
			list($line_name, $line_value) =explode('=',$line,2);
			$line_values =$this->split_csv_line($line_value);
			
			// [Entity]
			if ($st_cat == "Entity") {
			
				// テーブル物理名
				if ($line_name == "PName") {
				
					$t =$line_value;
					$s["Schema.tables.".$t] =array();
					
				// テーブル論理名
				} elseif ($line_name == "LName") {
				
					$s["Schema.tables.".$t]["label"] =$line_value;
					
				// フィールド
				} elseif ($line_name == "Field") {
				
					list(
						$lname, 
						$pname, 
						$sql_type, 
						$extra, 
						$keytype, 
						$_, 
						$comment
					) =$line_values;
					
					$s["Schema.cols.".$t][$pname]["label"] =$lname 
						? $lname
						: preg_replace('!\(.+$!','',$comment);
					
					list(
						$s["Schema.cols.".$t][$pname]["type"],
						$s["Schema.cols.".$t][$pname]["def.type"]
					) =$this->convert_sql_type($sql_type);
					
					if (strlen($keytype) && $keytype=="0") {
					
						$s["Schema.tables.".$t]["pkey"] =$pname;
					}
					
				// インデックス
				} elseif ($line_name == "Index") {
				
					$s["Schema.tables.".$t]["def.indexes"]
							=preg_replace('!^=(0,)?!','',$line_value);
				}
			}
		}
		
		report("Schema A5ER loaded.",array("schema" =>$s));
		
		// スクリプト生成
		$g =new ScriptGenerator;
		$g->node("root",array("p",array(
			array("c","Schama created from a5er-file."),
			array("v",array("c","registry",array(
				array("a",$this->get_array_script_node($s)),
			)))
		)));
		return $g->get_script();
	}
	
	//-------------------------------------
	// 
	protected function convert_sql_type ($sql_type) {
	
		$type ="text";
		$def_type ="text";
		
		if (preg_match('!^INT!',$sql_type)) {
		
			$def_type ="integer";
		
		} elseif (preg_match('!^VARCHAR!',$sql_type)) {
			
			$def_type ="string";
			
		} elseif (preg_match('!^DATE|DATETIME|TIMESTAMP!',$sql_type)) {
		
			$type ="dateselect";
			$def_type ="datetime";
		}
		
		return array($type, $def_type);
	}
	
	//-------------------------------------
	// 
	protected function split_csv_line ($line, $e='"', $d=',') {
		
		$csv_pattern ='/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'
				.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
		preg_match_all($csv_pattern, trim($line), $matches);
		$csv_data =(array)$matches[1];
		
		foreach ($csv_data as $k => $v) {
			
			$csv_data[$k] =preg_replace('!^'.$e.'(.*?)'.$e.'$!','$1',$v);
		}
		
		return $csv_data;
	}
	
	//-------------------------------------
	// CSVファイルを読み込んでSchemaのコードを生成する
	protected function load_schema_csv ($filename) {
		
		$csv =new CSVHandler($filename,"r",array(
			"file_charset" =>"SJIS-WIN",
		));
		
		$p ="nutral";
		$t =array();
		$s =array();
		
		foreach ($csv->read_all() as $c) {
			
			// コメント
			if ($c[0] == "#") {
				
				continue;
			}
			
			// 状態切り替え
			if (preg_match('!^#(.+)$!',$c[0],$match)) {
				
				$p =$match[1];
				continue;
			}
			
			// p:tables（tables/colsノード）
			// | table | col | label | def | type | other
			if ($p == "tables") {
				
				// cols
				// |  | col | label | def | type | other
				if (strlen($c[2]) && $t[1]) {
					
					$r =& $s["Schema.cols.".$t[1]][$c[2]];
					$this->parse_other($r, $c[3], "label");
					$this->parse_other($r, $c[4], "def.type");
					$this->parse_other($r, $c[5], "type");
					$this->parse_other($r, $c[6]);
				
				// tables
				// | table |  | label |  |  | 
				} elseif ($c[1]) {
				
					$t[1] =$c[1];
					
					$r =& $s["Schema.tables.".$t[1]];
					$this->parse_other($r, $c[3], "label");
					$this->parse_other($r, $c[6]);
				}
			}
			
			// p:controller（controllerノード）
			// | controller | label | type | table | account | other
			if ($p == "controller") {
				
				if ($c[1]) {
				
					$r =& $s["Schema.controller"][$c[1]];
					$this->parse_other($r, $c[2], "label");
					$this->parse_other($r, $c[3], "type");
					$this->parse_other($r, $c[4], "table");
					$this->parse_other($r, $c[5], "account");
					$this->parse_other($r, $c[6]);
				}
			}
		}
		
		report("Schema csv loaded.",array("schema" =>$s));
		
		// スクリプト生成
		$g =new ScriptGenerator;
		$g->node("root",array("p",array(
			array("c","Schama created from csv-file."),
			array("v",array("c","registry",array(
				array("a",$this->get_array_script_node($s)),
			)))
		)));
		return $g->get_script();
	}
	
	//-------------------------------------
	// 配列構造のScriptNodeを取得
	protected function get_array_script_node ($arr) {
		
		$n =array();
		
		foreach ($arr as $k => $v) {
			
			if (is_array($v)) {
			
				$n[$k] =array("a",$this->get_array_script_node($v));
			
			} elseif (is_numeric($v)) {
			
				$n[$k] =array("d",(int)$v);
				
			} else {
			
				$n[$k] =array("s",(string)$v);
			}
		}
		
		return $n;
	}
	
	//-------------------------------------
	// other属性のパース（改行=区切り）
	protected function parse_other ( & $ref, $str, $default_key=null) {
		
		foreach (preg_split("!\n|\|!",$str) as $sets) {
			
			if (preg_match('!^(.+?)=(.+)$!',$sets,$match))  {
				
				$ref[trim($match[1])] =trim($match[2]);
				
			} elseif ($default_key) {
			
				$ref[$default_key] =trim($sets);
			}
		}
	}
}