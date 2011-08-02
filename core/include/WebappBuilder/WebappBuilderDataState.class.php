<?php

//-------------------------------------
// 
class WebappBuilderDataState extends WebappBuilder {
	
	//-------------------------------------
	// DBの状態を設定する
	public function fetch_datastate () {
		
		// 指定しなければdefault
		$connection =DBI::change($this->options["connection"]);
		
		// DBのダンプデータを作成する
		if ($this->options["create_dump"]) {
			
			$dump_file =tempnam("/tmp","php_tmpfile-");
			DBI::load()->create_dump($dump_file);
			
			clean_output_shutdown(array(
				"download" =>"dump-".$connection."-".date("Ymd").".sql",
				"file" =>$dump_file,
			));
		}
		
		// DBのダンプデータをリストアする
		if ($this->options["restore_dump"]) {
			
			$dump_file =$_FILES["dump_sql"]["tmp_name"];
			DBI::load()->restore_dump($dump_file,0);
		}
		
		// CSVファイルを読み込んでデータの操作を行う
		if ($this->options["restore_ds"]) {
			
			$filename =$_FILES["ds_csv"]["tmp_name"];
			$this->restore_ds_csv($filename);
		}
		
		// CSVファイルを書き出す
		if ($this->options["create_ds"]) {
			
			$filename =tempnam("/tmp","php_tmpfile-");
			$this->create_ds_csv($filename);
			
			clean_output_shutdown(array(
				"download" =>"ds-".$connection."-".date("Ymd").".csv",
				"file" =>$filename,
			));
		}
	}
	
	//-------------------------------------
	// CSVファイルを書き出す
	protected function create_ds_csv ($filename) {
		
		// DBデータ構造読み込み
		$schema =array();
		
		foreach (DBI::load()->desc_tables() as $t) {
			
			foreach (DBI::load()->desc($t) as $k => $col_info) {
			
				$schema[$t]["label"][] =$k;
				$schema[$t]["label_comment"][] =$col_info["comment"];
			}
			
			foreach (DBI::load()->select(array("table"=>$t)) as $index => $data) {
				
				foreach ($data as $k => $v) {
					
					if ($v === "") {
						
						$v ="____";
					}
					
					$k =preg_replace('!^.*?\.!','',$k);
					$schema[$t]["data"][$index][$k] =$v;
				}
			}
		}
		
		$csv =new CSVHandler($filename,"w",array(
			"file_charset" =>"SJIS-WIN",
		));
		
		// CSV書き込み
		foreach ($schema as $t => $table_info) {
			
			
			$line =$table_info["label_comment"];
			array_unshift($line,"#");
			$csv->write_line($line);
			
			$line =$table_info["label"];
			array_unshift($line,$t);
			$csv->write_line($line);
			
			foreach ($table_info["data"] as $index => $data) {
				
				$line =array("");
				
				foreach ($table_info["label"] as $k) {
					
					$line[] =$data[$k];
				}
				
				$csv->write_line($line);
			}
			
			$csv->write_line(array());
		}
		
		report("Datastate-CSV create successfuly",array(
				"schema" =>$schema));
					
		return true;
	}
	
	//-------------------------------------
	// CSVファイルを読み込んでデータの操作を行う
	protected function restore_ds_csv ($filename) {
		
		$csv =new CSVHandler($filename,"r",array(
			"file_charset" =>"SJIS-WIN",
		));
		
		$p ="nutral";
		$t ="";
		$schema =array();
		
		// CSV解析
		foreach ($csv->read_all() as $c) {
			
			// コメントと空行
			if ($c[0] == "#" || ! strlen(implode("",$c))) {
				
				continue;
			}
			
			// 状態切り替え
			if (preg_match('!^#(.+)$!',$c[0],$match)) {
				
				$p =$match[1];
				continue;
			}
			
			// p:nutral
			if ($p == "nutral") {
				
				// 見出し
				// table | * | * ...
				if ($c[0]) {
				
					$t =$c[0];
					unset($c[0]);
					$schema[$t]["label"] =$c;
				
				// データ
				//       | * | * ...
				} elseif ($t) {
					
					$line =array();
					
					foreach ($c as $k => $v) {
						
						$label =$schema[$t]["label"][$k];
						
						if ($v == "____") {
						
							$line[$label] ="";
							
						} elseif ($k && $label) {
						
							$line[$label] =$v;
						}
					}
					
					$schema[$t]["data"][] =$line;
				}
			}
		}
		
		// データ更新
		DBI::load()->begin();
		
		try {
			
			// データの削除と登録
			foreach ($schema as $t => $table_info) {
				
				DBI::load()->delete(array("table" =>$t, "conditions"=>"1=1"));
			
				foreach ((array)$table_info["data"] as $fields) {
				
					DBI::load()->insert(array("table" =>$t, "fields" =>$fields));
				}
			}
			
			DBI::load()->commit();
		
			report("Datastate-CSV restore successfuly",array(
					"schema" =>$schema));
			
			return true;
			
		} catch (Exception $e) {
		
			DBI::load()->rollback();
			
			report_warning("Datastate-CSV restore aborted",array(
					"schema" =>$schema));
			
			return false;
		}
	}
}