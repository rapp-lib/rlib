<?php

//-------------------------------------
// DBI実装
class DBI_Base {

	protected $name ="";
	protected $ds =null;
	protected $transaction_stack =array();
	private $desc_cache =array();
	
	//-------------------------------------
	// 初期化
	public function __construct ($name) {
	
		$this->name =$name;
	}
	
	//-------------------------------------
	// DB接続
	public function connect ($connect_info) {
		
		if ($connect_info["driver"]) {
		
			require_once(LIBS.'/model/datasources/dbo/'
					.'dbo_'.$connect_info["driver"].'.php');
		}
		
		ConnectionManager::create($this->name,$connect_info);
		$this->ds =ConnectionManager::getDataSource($this->name);
	}
	
	//-------------------------------------
	// Datasourceの取得
	public function get_datasource () {
		
		return $this->ds;
	}
	
	//-------------------------------------
	// Datasourceの実装の読み込み
	public function __call ($func ,$args) {
	
		return call_user_func_array(array($this->ds,$func),$args);
	}
	
	//-------------------------------------
	// 最後のSQLエラーを取得
	public function get_last_error () {
		
		return $this->ds->lastError();
	}
	
	//-------------------------------------
	// テーブル一覧を取得
	public function desc_tables () {
		
		if ( ! $this->desc_cache["__TABLES__"]) {
			
			$this->desc_cache["__TABLES__"] =$this->ds->listSources();
		}
		
		return $this->desc_cache["__TABLES__"];
	}
	
	//-------------------------------------
	// テーブル構造解析
	public function desc ($table_name) {
		
		if ( ! $this->desc_cache[$table_name]) {
			
			$this->desc_cache[$table_name] =$this->ds->describe($table_name);
		}
		
		return $this->desc_cache[$table_name];
	}
	
	//-------------------------------------
	// LAST_INSERT_IDの取得
	public function last_insert_id ($table_name=null, $pkey_name=null) {
		
		return $this->ds->lastInsertId($table_name,$pkey_name);
	}
	
	//-------------------------------------
	// NumRowsの取得
	public function last_num_rows () {
		
		return $this->ds->lastNumRows();
	}
	
	//-------------------------------------
	// Affectedの取得
	public function last_affected () {
		
		return $this->ds->lastAffected();
	}
	
	//-------------------------------------
	// SQL実行
	public function exec (
			$st, 
			$report_context=array()) {
		
		$start_time =microtime(true);
		
		$result =$this->ds->execute($st);
		
		$elapsed =round((microtime(true) - $start_time)*1000,2)."ms";
		
		// SQL文の調査
		if (get_webapp_dync("report") && ! $this->ds->error) {
			
			$explain =$this->analyze_sql($st,$elapsed);
			
			if ($explain["msg"]) {
				
				$report_context["Explain"] =$explain["msg"];
			}
		}
		
		report('Execute Statement',array_merge($report_context,array(
			"Statement" =>$st,
			"Elapsed" =>$elapsed,
			"NumRows" =>"N:".count($result)."/A:".$this->ds->lastAffected(),
		)));
		
		if ($explain["warn"]) {
			
			foreach ($explain["warn"] as $warn) {
				
				report('Bad SQL '.$warn,array(
					"Statement" =>$st,
					"Full Explain" =>$explain["full"],
				));
			}
		}
		
		if ($this->ds->error) {
			
			report_warning('SQL Error',array(
				"Statement" =>$st,
				"Error" =>$this->ds->error,
			));
			
			// トランザクション起動中であれば例外発行
			if ($this->transaction_stack) {
				
				throw new DBIException($this->ds->error);
			}
		}
		
		return $result;
	}
	
	//-------------------------------------
	// Select結果を1件ロード
	public function fetch ($result_source) {
		
		$this->ds->_result =$result_source;
		
		// 結果セットが無効であればnullを返す
		if ( ! $this->ds->hasResult()) {
			
			return null;
		}
		
		$this->ds->resultSet($this->ds->_result);
		$result =$this->ds->fetchResult();
		
		// データがなければnullを返す
		if ( ! $result) {
		
			return null;
		}
		
		// 階層構造の変更（$a[Alias][Key] => $a[Alias.Key]）
		$result_copy =array();
		
		foreach ((array)$result as $k1 => $v1) {
			
			foreach ((array)$v1 as $k2 => $v2) {
			
				$key =is_numeric($k1) ? $k2 : $k1.".".$k2;
				$result_copy[$key] =& $result[$k1][$k2];
			}
		}
		
		return $result_copy;
	}
	
	//-------------------------------------
	// Select結果を全件ロード
	public function fetch_all ($result_source) {
		
		$result =array();
		
		while ($row =$this->fetch($result_source)) {
			
			$result[] =$row;
		}
		
		return $result;
	}
	
	//-------------------------------------
	// トランザクションのBegin
	public function begin ($transaction_id="default") {
		
		if ( ! $this->transaction_stack) {
		
			$this->exec($this->ds->_commands['begin']);
		}
		
		array_push($this->transaction_stack,$transaction_id);
	}
	
	//-------------------------------------
	// トランザクションのCommit
	public function commit ($transaction_id="default") {
		
		if ( ! $this->transaction_stack) {
			
			report("Transaction has rollbacked, not commit");
		}
		
		$target_transaction_id =array_pop($this->transaction_stack);
		
		if ($transaction_id != $target_transaction_id) {
			
			report_error("Nested Transaction commit  error",array(
				"target_transaction" =>$transaction_id,
				"missing_transaction" =>$target_transaction_id,
			));
		}
		
		if ( ! $this->transaction_stack) {
		
			$this->exec($this->ds->_commands['commit']);
		}
	}
	
	//-------------------------------------
	// トランザクションのRollback
	public function rollback ($transaction_id="default") {
		
		if ( ! $this->transaction_stack) {
			
			report("Transaction has rollbacked, not rollback");
		}
		
		$target_transaction_id =array_pop($this->transaction_stack);
		
		if ($transaction_id != $target_transaction_id) {
			
			report_error("Nested Transaction rollback  error",array(
				"target_transaction" =>$transaction_id,
				"missing_transaction" =>$target_transaction_id,
			));
		}
		
		$this->exec($this->ds->_commands['rollback']);
		
		$this->transaction_stack =array();
	}
	
	//-------------------------------------
	// Query実行(取得は行わない)
	public function select_nofetch ($query) {
	
		$st =$this->st_select($query);
		$result =$this->exec($st,array(
			"Type" =>"select_nofetch",
			"Query" =>$query,
		));
		return $result;
	}
	
	//-------------------------------------
	// Query実行(全件取得)
	public function select ($query) {
	
		$st =$this->st_select($query);
		$result =$this->exec($st,array(
			"Type" =>"select",
			"Query" =>$query,
		));
		$ts =$this->fetch_all($result);
		
		return $ts;
	}
	
	//-------------------------------------
	// Query実行(1件のデータ取得)
	public function select_one ($query) {
		
		$st =$this->st_select($query);
		$result =$this->exec($st,array(
			"Type" =>"select_one",
			"Query" =>$query,
		));
		$t =$this->fetch($result);
		
		return $t;
	}
	
	//-------------------------------------
	// Query実行(件数取得)
	public function select_count ($query) {
		
		$query["fields"] =array("COUNT(*) AS count");
		
		unset($query["limit"]);
		unset($query["offset"]);
		unset($query["order"]);
		
		$st =$this->st_select($query);
		$result =$this->exec($st,array(
			"Type" =>"select_count",
			"Query" =>$query,
		));
		$t =$this->fetch($result);
		$count =(int)$t["count"];
		
		return $count;
	}
	
	//-------------------------------------
	// Query実行(Pager取得)
	public function select_pager ($query) {
	
		$query["fields"] =array("COUNT(*) AS count");
		$offset =$query["offset"];
		$limit =$query["limit"];
		$paging_slider =$query["paging_slider"] 
				? $query["paging_slider"]
				: 10 ;
		
		unset($query["paging_slider"]);
		unset($query["limit"]);
		unset($query["offset"]);
		unset($query["order"]);
		
		if ( ! $limit) {
			
			return null;
		}
		
		$count =0;
		
		// GROUP BY指定がある場合はCOUNT結果を再集計
		if ($query["group"]) {
		
			$st =$this->st_select($query);
			$result =$this->exec($st,array(
				"Type" =>"select_pager",
				"Query" =>$query,
			));
			$ts =$this->fetch_all($result);
			
			foreach ($ts as $t) {
			
				$count +=(int)$t["count"];
			}
			
		// 件数を集計
		} else {
		
			$st =$this->st_select($query);
			$result =$this->exec($st,array(
				"Type" =>"select_pager",
				"Query" =>$query,
			));
			$t =$this->fetch($result);
			$count =(int)$t["count"];
		}
		
		$pager =$this->build_pager($offset,$limit,$count,$paging_slider);
		
		return $pager;
	}
	
	//-------------------------------------
	// Query実行(INSERT)
	public function insert ($query) {
	
		$st =$this->st_insert($query);
		$result =$this->exec($st,array(
			"Type" =>"insert",
			"Query" =>$query,
		));
		
		return $result;
	}
	
	//-------------------------------------
	// Query実行(UPDATE)
	public function update ($query) {
	
		$st =$this->st_update($query);
		
		$result =$this->exec($st,array(
			"Type" =>"update",
			"Query" =>$query,
		));
		
		return $result;
	}
	
	//-------------------------------------
	// Query実行(DELETE)
	public function delete ($query) {
	
		$st =$this->st_delete($query);
		
		$result =$this->exec($st,array(
			"Type" =>"delete",
			"Query" =>$query,
		));
		
		return $result;
	}
	
	//-------------------------------------
	// SQL組み立て（JOIN句）
	public function st_joins ($joins) {
		
		if (is_array($joins) && $joins) {
			
			foreach ($joins as $k => $v) {
				
				if (is_array($v)) {
				
					// joins.N:(table,conditions,type)構造の展開
					if (isset($v[0])) {
						
						$joins[$k]["table"] =$v[0];
						unset($joins[$k][0]);
						
						if (isset($v[1])) {
							
							$joins[$k]["conditions"] =$v[1];
							unset($joins[$k][1]);
						}
						
						if (isset($v[2])) {
							
							$joins[$k]["type"] =$v[2];
							unset($joins[$k][2]);
						}
					}
					
					// table:(table,alias)構造の展開
					if (is_array($joins[$k]["table"])
							&& $joins[$k]["table"][0]) {
						
						list(
							$joins[$k]["table"],
							$joins[$k]["alias"]
						) =$joins[$k]["table"];
					}
					
					// サブクエリの解決
					if (is_array($joins[$k]["table"])) {
						
						$joins[$k]["table"] 
								='('.$this->st_select($joins[$k]["table"]).')';
					}
					
					// 標準JOIN方式をINNERからLEFTに変更
					if ( ! isset($joins[$k]["type"]) 
							&& registry("DBI.statement.default_join_type")) {
						
						$joins[$k]["type"] =registry("DBI.statement.default_join_type");
					}
					
					$joins[$k] =$this->ds->buildJoinStatement($joins[$k]);
				}
			}
		}
		
		return $joins;
	}
	
	//-------------------------------------
	// SQL組み立て（SELECT）
	public function st_select ($query) {
		
		$default_query =array(
			'fields' => array("*"),
			'conditions' => array(),
			'table' => null,
			'alias' => $query["table"],
			'limit' => null,
			'offset' => null,
			'joins' => array(),
			'order' => null,
			'group' => null,
		);
		$query =array_merge($default_query,$query);
		
		// table:(table,alias)構造の展開
		if (is_array($query["table"]) && $query["table"][0]) {
		
			list($query["table"],$query["alias"]) =$query["table"];
		}
		
		// Postgres向けに全てのfieldsのaliasを"AS TTT__AAA"に設定する
		if (get_class($this->ds) == "DboPostgres") {
			
			// aliasの取得
			$aliases =array();
			$alias_name =$query["alias"] 
					? $query["alias"] 
					: $query["table"];
			$aliases[$alias_name] =array(
					"table" =>$query["table"], 
					"alias" =>$alias_name);
			
			foreach ($query["joins"] as $join_query) {
			
				$alias_name =$join_query["alias"] 
						? $join_query["alias"] 
						: $join_query["table"];
				$aliases[$alias_name] =array(
						"table" =>$join_query["table"], 
						"alias" =>$alias_name);
			}
			
			$query["fields"] =$this->rename_fields_for_postgres($query["fields"],$aliases);
		}
			
		// サブクエリの解決
		if (is_array($query["table"])) {
			
			$query["table"] ='('.$this->st_select($query["table"]).')';
		}
		
		// joins
		$query["joins"] =$this->st_joins($query["joins"]);
		
		// conditions
		$query["conditions"] =$this->ds->conditions($query["conditions"],true,false);
		
		$st =$this->ds->buildStatement($query,$model=null);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（INSERT）
	public function st_insert ($query) {
		
		$default_query =array(
			'table' => null,
			'alias' => $query["table"],
			'fields' => array(),
		);
		$query =array_merge($default_query,$query);
		
		// table:(table,alias)構造の展開
		if (is_array($query["table"])) {
		
			list($query["table"],$query["alias"]) =$query["table"];
		}
		
		// fields, values
		$insert_fields =array();
		$insert_values =array();
		
		foreach ((array)$query["fields"] as $k => $v) {
		
			if (strpos($k,".") !== false) {
				
				if (strlen($query["alias"])
						&& preg_match('!^'.$query["alias"].'\.([^\.]+)$!',$k,$match)) {
					
					$k =$match[1];
					
				} else {
				
					continue;
				}
			}
			
			if (is_array($v)) {
				
				$v =serialize($v);
			}
			
			$insert_fields[] =$this->ds->name($k);
			$insert_values[] =$this->ds->value($v, "string", false);
		}
		
		$query["fields"] =implode(", ",$insert_fields);
		$query["values"] =implode(", ",$insert_values);
		
		$st =$this->ds->renderStatement('create',$query);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（UPDATE）
	public function st_update ($query) {
		
		$default_query =array(
			'fields' => array(),
			'conditions' => array(),
			'table' => null,
			'joins' => null,
		);
		$query =array_merge($default_query,$query);
		
		// table:(table,alias)構造の展開
		if (is_array($query["table"])) {
		
			list($query["table"],$query["alias"]) =$query["table"];
		}
		
		// alias
		if ( ! $query["alias"]) {
			
			$query["alias"] =$query["table"];
		}
		
		// joins
		$query["joins"] =$this->st_joins($query["joins"]);
		$query["joins"] =implode(' ',(array)$query["joins"]);
		
		// fields
		$update_fields =array();
		
		foreach ($query["fields"] as $k => $v) {
		
			// Postgresならばfieldsのaliasを削除
			if (get_class($this->ds) == "DboPostgres"
					&& preg_match('!^(?:[^\.]+\.)([^\.]+)$!',$k,$match)) {
					
				$k =$match[1];
			}
			
			if ($v === null) {
				
				$update_fields[] =$this->ds->name($k)." = NULL"; 
			
			} elseif (is_numeric($k)) {
			
				$update_fields[] =$v;
			
			} elseif (is_array($v)) {
				
				$update_fields[] =$this->ds->name($k)
						." = ".$this->ds->value(serialize($v), "string", false);
				
			} else {
			
				$update_fields[] =$this->ds->name($k)
						." = ".$this->ds->value($v, "string", false);
			}
		}
		
		$query["fields"] =implode(", ",$update_fields);
		
		// conditions
		$query["conditions"] =$this->ds->conditions($query["conditions"]);
		
		$st =$this->ds->renderStatement('update',$query);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（DELETE）
	public function st_delete ($query) {
		
		$default_query =array(
			'alias' => null,
			'joins' => null,
			'table' => null,
			'conditions' => array(),
		);
		$query =array_merge($default_query,$query);
		
		// table:(table,alias)構造の展開
		if (is_array($query["table"])) {
		
			list($query["table"],$query["alias"]) =$query["table"];
		}
		
		// alias
		if ( ! $query["alias"]) {
			
			$query["alias"] =$query["table"];
		}
		
		// joins
		$query["joins"] =$this->st_joins($query["joins"]);
		$query["joins"] =implode(' ',(array)$query["joins"]);
		
		// conditions
		$query["conditions"] =$this->ds->conditions($query["conditions"]);
		
		$st =$this->ds->renderStatement('delete',$query);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（CREATE TABLE）
	public function st_init_schema ($query) {
		
		$table_def =$query["table_def"];
		
		$table =$table_def["table"];
		$columns = $indexes = $tableParameters = array();
		
		// column設定
		foreach ((array)$table_def["cols"] as $col) {
			
			$columns[] =$this->ds->buildColumn($col);
		}
		
		// indexes設定
		if ($table_def["indexes"]) {
		
			$col =$table_def["indexes"];
			$indexes =array_merge($indexes, $this->ds->buildIndex($col, $table));
		}
		
		// pkey-index設定
		if ($table_def["pkey"]) {
			
			$col =array('PRIMARY' =>array('column' =>$table_def["pkey"], 'unique' => 1));
			$indexes =array_merge($indexes, $this->ds->buildIndex($col, $table));
		}

		// tableParameters設定
		if ($table_def["tableParameters"]) {
			
			$col =$table_def["tableParameters"];
			$tableParameters =$this->ds->buildTableParameters($col, $table);
		}
		
		$st =$this->ds->renderStatement('schema', 
				compact('table', 'columns', 'indexes', 'tableParameters'));
				
		return $st;
	}
	
	//-------------------------------------
	// pagerの作成
	public function build_pager ($offset, $length, $total ,$slider=10) {
		
		$pager =array();
		
		$total_dup =$total ? $total : 1; 
		
		// 総ページ数
		$pages =ceil($total_dup/$length);
		$pager['numpages'] =$pages;

		// 最初のページと最後のページ
		$pager['firstpage'] =1;
		$pager['lastpage'] =$pages;

		// ページ配列の作成
		$pager['pages'] = array();
		
		for ($i=1; $i <= $pages; $i++) {
			
			$coffset = $length * ($i-1);
			
			$pager['pages'][$i] =$coffset;

			if ($coffset == $offset) {
				
				$pager['current'] = $i;
			}
		}
		
		if( ! isset($pager['current'])) {
			
			$pager['current'] =0;
		}

		// ページ長
		if ($maxpages) {
			
			$radio = floor($maxpages/2);
			$minpage = $pager['current'] - $radio;
			
			if ($minpage < 1) {
				
				$minpage = 1;
			}
			
			$maxpage = $pager['current'] + $radio - 1;
			
			if ($maxpage > $pager['numpages']) {
				
				$maxpage = $pager['numpages'];
			}
		
			$pager['maxpages'] = $maxpages;
			
		} else {
			
			$pager['maxpages'] = null;
		}
		
		// 前ページ
		$prev = $offset - $length;
		$pager['prev'] = ($prev >= 0) ? $prev : null;
		
		// 次ページ
		$next = $offset + $length;
		$pager['next'] = ($next < $total) ? $next : null;
		
		// 残りのページ数
		if ($pager['current'] == $pages) {
			
			$pager['remain'] = 0;
			$pager['to'] = $total;
			
		} else {
			
			if ($pager['current'] == ($pages - 1)) {
				
				$pager['remain'] = $total - ($length*($pages-1));
			
			} else {
				
				$pager['remain'] = $length;
			}
			
			$pager['to'] = $pager['current'] * $length;
		}

		$pager['from'] = (($pager['current']-1) * $length)+1;
		$pager['total'] =$total;
		$pager['offset'] =$offset + 1;
		$pager['length'] =$length;
		
		// スライダーの構築
		if ($slider) {
			
			$pager =$this->build_slider($pager,$slider);
		}
		
		return $pager;
	}
	
	//-------------------------------------
	// スライダーの構築
	public function build_slider ($pager ,$slider){
			
		$pager['pages_slider'] =array();
		
		$start =1;
		$prev_set =null;
		$next_set =$slider+1;
		$current =$pager['current'];
		$pages_count =count($pager['pages']);
		
		if ($current+ceil($slider/2) >= $pages_count 
				&& $pages_count-$slider>0) {
		
			$start =$pages_count - $slider + 1;

		} elseif ($current-floor($slider/2) > 0) {
		
			$start =$current - floor($slider/2);
		}

		for ($i=$start; $i<$start+$slider && $i<=$pages_count; $i++) {
		
			$pager['pages_slider'][$i] =$pager['pages'][$i];
		}

		$pager['slider_prev'] =isset($pager['pages'][$pager['current']-$slider-1])
				? $pager['pages'][$pager['current']-$slider-1]
				: null;
		
		$pager['slider_next'] =isset($pager['pages'][$pager['current']+$slider+1])
				? $pager['pages'][$pager['current']+$slider+1]
				: null;
				
		$pager['pages_raw'] =$pager['pages'];
		$pager['pages'] =$pager['pages_slider'];
			
		return $pager;
	}
	
	//-------------------------------------
	// Postgres向けに全てのfieldsのaliasを設定する
	private function rename_fields_for_postgres ($raw_fields,$aliases) {
		
		$fields =array();
		$field_pattern_tfa ='!^\s*(?:([_\w\*]+)\.)?([_\w\*]+)(?:\s+AS\s+([_\w\*]+))?\s*$!i';
		$field_pattern_other ='!^(.*?)\s+AS\s+([_\w\*]+)\s*$!i';
		
		// aliasに対する実fieldの一覧取得
		foreach ($aliases as $i => $alias) {
			
			$aliases[$i]["desc"] =$this->desc($alias["table"]);
		}
		
		// fieldsの変換
		foreach ($raw_fields as $field) {
			
			$field_error =false;
			
			// 文字列以外の型
			if ( ! is_string(field)) {
				
				$fields[] =$field;
			
			// "TTT.FFF AS AAA"形式の処理
			} elseif (preg_match($field_pattern_tfa,$field,$match)) {
			
				list(,$_t,$_f,$_a) =$match;
				
				// TTT.FFF
				if ($_t && $_f && $_t!="*" && $_f!="*") {
				
					$fields[] ='"'.$_t.'".'.'"'.$_f
							.'" AS "'.$_t.'__'.($_a ? $_a : $_f).'"';
					
				// FFF
				} elseif ( ! $_t && $_f && $_f!="*") {
				
					foreach ($aliases as $_rt => $alias) {
					
						foreach ($alias["desc"] as $_rf => $_df) {
						
							if ($_rf == $_f) {
							
								$fields[] ='"'.$_rt.'".'.'"'.$_f
										.'" AS "'.$_rt.'__'.($_a ? $_a : $_f).'"';
								break 2;
							}
						}
					}
					
				// TTT.*
				} elseif ($_t && $_f && $_t!="*" && $_f=="*") {
				
					foreach ($aliases[$_t]["desc"] as $_rf => $_df) {
					
						$fields[] ='"'.$_t.'".'.'"'.$_rf.'" AS "'.$_t.'__'.$_rf.'"';
					}
					
				// *
				} elseif (( ! $_t || $_t=="*") && $_f && $_f=="*") {
				
					foreach ($aliases as $_rt => $alias) {
					
						foreach ((array)$alias["desc"] as $_rf => $_df) {
						
							$fields[] ='"'.$_rt.'".'.'"'.$_rf.'" AS "'.$_rt.'__'.$_rf.'"';
						}
					}
				}
				
			// "... AS AAA"形式の処理
			} elseif (preg_match($field_pattern_other,$field,$match)) {
				
				$fields[] =$field;
							
			} else {
			
				report_warning('Invalid Query-field.',array(
					"field" =>$field,
				));
			}
		}
		
		return $fields;
	}
	
	//-------------------------------------
	// DBDumpの生成
	public function create_dump ($filename, $compress=false) {
		
		$info =$this->ds->config;
		
		$cmd ="";
		
		if ($info["driver"] == "postgres") {
			
			// パスワードは"~/.pgpass"で設定する必要があります
			// 設定は"HOST:PORT:DBNAME:USER:PASS"の形式
			$cmd ="pg_dump -c";
			if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
			if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
			if ($info["login"]) { $cmd .=" -U ".$info["login"]; }
			if ($info["database"]) { $cmd .=" -D ".$info["database"]; }
			$cmd .=' | gzip -c9 > "'.$filename.'"';
			
		} elseif ($info["driver"] == "mysql" || $info["driver"] == "mysqli") {
			
			$cmd ="mysqldump";
			if ($info["encoding"]) { $cmd .=" --default-character-set=".$info["encoding"]; }
			if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
			if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
			if ($info["login"]) { $cmd .=" -u".$info["login"]; }
			if ($info["password"]) { $cmd .=" -p".$info["password"]; }
			if ($info["database"]) { $cmd .=" ".$info["database"]; }
			$cmd .=' 1> "'.$filename.'"';
		
		} else {
			
			report_error("DB driver ".$info["driver"]." is not support DBI::create_dump");
		}
		
		exec($cmd,$output,$return_var);
		$result = ! $return_var;
		
		// 圧縮する
		if ($compress && $result) {
			
			$cmd_gzip ='gzip -cf "'.$filename.'" > "'.$filename.'"';
			exec($cmd_gzip,$output,$return_var);
			$result = ! $return_var;
		}
		
		$report_params =array("cmd"=>$cmd);
		$result 
				? report('Dump create successfly',$report_params)
				: report_warning('Dump create failur',$report_params);
		
		return $result;
	}
	
	//-------------------------------------
	// DBDumpのリストア
	public function restore_dump ($filename, $compress=false) {
		
		$info =$this->ds->config;
		
		// 解凍する
		if ($compress) {
			
			$cmd_gzip ='gzip -cdf "'.$filename.'" > "'.$filename.'"';
			exec($cmd_gzip,$output,$return_var);
			$result = ! $return_var;
		}
		
		$cmd ="";
		
		if ($info["driver"] == "postgres") {
			
			// パスワードは"~/.pgpass"で設定する必要があります
			// 設定は"HOST:PORT:DBNAME:USER:PASS"の形式
			$cmd ='psql';
			if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
			if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
			if ($info["login"]) { $cmd .=" -U ".$info["login"]; }
			if ($info["database"]) { $cmd .=" ".$info["database"]; }
			$cmd .=' < "'.$filename.'" 2>&1';
			
		} elseif ($info["driver"] == "mysql" || $info["driver"] == "mysqli") {
			
			$cmd ='mysql';
			if ($info["encoding"]) { $cmd .=" --default-character-set=".$info["encoding"]; }
			if ($info["host"]) { $cmd .=" -h ".$info["host"]; }
			if ($info["port"]) { $cmd .=" -P ".$info["port"]; }
			if ($info["login"]) { $cmd .=" -u".$info["login"]; }
			if ($info["password"]) { $cmd .=" -p".$info["password"]; }
			if ($info["database"]) { $cmd .=" ".$info["database"]; }
			$cmd .=' < "'.$filename.'" 2>&1';
		
		} else {
			
			report_error("DB driver ".$info["driver"]." is not support DBI::restore_dump");
		}
		
		exec($cmd,$output,$return_var);
		$result = ! $return_var;
		
		$report_params =array(
				"cmd"=>$cmd,
				"output"=>$output);
		$result 
				? report('Dump restore successfly',$report_params)  
				: report_warning('Dump restore failur',$report_params) ;
		
		return $return_var;
	}
	
	//-------------------------------------
	// SQL文を評価して問題を指摘
	public function analyze_sql ($st, $elapsed=0) {
		
		if (get_class($this->ds) == "DboPostgres"
				|| ! preg_match('!^SELECT\s!is',$st)) {
			
			return null;
		}
		
		$result =$this->ds->execute("EXPLAIN ".$st);
		$ts =$this->fetch_all($result);
		
		$explain["full"] =array();
		$explain["msg"] =array();
		$explain["warn"] =array();
		
		foreach ($ts as $i =>$t) {
			
			$t["Extra"] =array_map("trim",explode(';',$t["Extra"]));
			
			$msg =$t["select_type"];
			
			if ($t["type"]) { 
				
				$msg .=".".$t["type"]; 
			}
			
			if ($t["table"]) { 
				
				$msg .=" , Table: ".$t["table"]; 
			}
			
			if ($t["rows"]) { 
				
				$msg .="(".$t["rows"].")"; 
			}
				
			if ($t["key"]) { 
				
				$msg .=" , Index: ".$t["key"]; 
			}
			
			if ($t["Extra"]) { 
				
				$msg .=" , (".implode("|",$t["Extra"]).")"; 
			}
			
			$explain["msg"][] =$msg;
			
			$full =$t;
			$full["Extra"] =implode(",",$t["Extra"]);
			
			if ($t["type"] == "index") {
				
				$explain["warn"][] ="[効果的ではないINDEX] ".$msg;
			}
			
			if ($t["type"] == "ALL") {
				
				$explain["warn"][] ="[全件スキャン] ".$msg;
			}
			
			if ($t["select_type"] == "DEPENDENT SUBQUERY" && $t["type"] != "ref") {
				
				$explain["warn"][] ="[INDEXなし相関SQ★] ".$msg;
			
			}
			
			if ($t["select_type"] == "DEPENDENT SUBQUERY" && $t["type"] == "ref") {
				
				$explain["warn"][] ="[INDEX相関SQ] ".$msg;
			}
			
			foreach ($t["Extra"] as $extra_msg) {
				/*
				if ($extra_msg == "Using filesort") {
				
					$explain["warn"][] ="[INDEXのないソート] ".$msg;
				}
				*/
				/*
				if ($extra_msg == "Using temporary") {
				
					$explain["warn"][] ="[一時テーブルの生成] ".$msg;
				}
				*/
				
				if ($extra_msg == "Using join buffer") {
				
					$explain["warn"][] ="[全件スキャンJOIN★] ".$msg;
				}
			}
		}
			
		return $explain;
	}
	
	//-------------------------------------
	// プレースホルダーへの値の設定
	public function bind ($name=null, $value=null) {
		
		return array_registry($this->ds->bounds,$name,$value);
	}
}
