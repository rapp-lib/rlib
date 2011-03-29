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
		
		$src_file_find =registry("Path.webapp_dir")."/config/schema.config.csv";
		$src_file =find_include_path($src_file_find);
		$dest_file =registry("Path.webapp_dir")."/config/schema.config.php";
		
		if ($src_file) {
		
			$src =$this->load_schema_csv($src_file);
			return $this->deploy_src($dest_file,$src);
		
		} else {
			
			report_warning("Schema-csv is-not found.",array(
				"src_file_find" =>$src_file_find,
			));
			print "<pre>".htmlspecialchars($src)."</pre>";
		}
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
					$r =array(
						"label" =>"",
						"def" =>"",
						"type" =>"",
					);
					$this->parse_other($r["label"], $c[3]);
					$this->parse_other($r["def"], $c[4]);
					$this->parse_other($r["type"], $c[5]);
					$this->parse_other($r,$c[6]);
				
				// tables
				// | table |  | label |  |  | 
				} elseif ($c[1]) {
				
					$t[1] =$c[1];
					
					$r =& $s["Schema.tables.".$t[1]];
					$r =array(
						"label" =>""
					);
					$this->parse_other($r["label"], $c[3]);
					$this->parse_other($r,(string)$c[6]);
				}
			}
			
			// p:controller（controllerノード）
			// | controller | label | type | table | account | other
			if ($p == "controller") {
				
				if ($c[1]) {
				
					$r =& $s["Schema.controller"][$t[1]];
					$r =array(
						"label" =>"",
						"type" =>"",
						"table" =>"",
						"account" =>"",
					);
					$this->parse_other($r["label"], $c[2]);
					$this->parse_other($r["type"], $c[3]);
					$this->parse_other($r["table"], $c[4]);
					$this->parse_other($r["account"], $c[5]);
					$this->parse_other($r,$c[6]);
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
	protected function parse_other ( & $ref, $str) {
		
		if ( ! preg_match('!=!',$str) && $str) {
			
			$ref =trim($str);
			
		} else {
			
			foreach (explode("\n",$str) as $sets) {
				
				list($k ,$v) =explode('=',trim($sets));
				
				if ($k && $v) {
				
					$v_ref =&ref_array($ref,$k);
					$v_ref =$v;
				}
			}
		}
	}
}