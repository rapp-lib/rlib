<?php
/*
	Sample:
		
		// 接続
		DBI::load()->connect(array(
			'driver' => 'mysql',
			'persistent' => false,
			'host' => 'localhost',
			'login' => 'root',
			'password' => 'pass',
			'database' => 'rlib2_test',
			'prefix' => '',
		));
		
		// INSERT
		$r =DBI::load()->insert(array(
			'table' => "member",
			'alias' => "Member",
			'fields' => array(
				"Member.name" =>"Toyosawa_NEW",
			),
		));
		var_dump($r);
		print "<hr/>";
		
		// UPDATE
		$r =DBI::load()->update(array(
			'table' => "member",
			'alias' => "Member",
			'fields' => array(
				"Member.name" =>"NEW_RECORD",
			),
			'conditions' => array(
				"Member.name LIKE" =>"%_NEW",
			),
		));
		var_dump($r);
		print "<hr/>";
		
		// SELECT
		$r =DBI::load()->exec(DBI::load()->st_select(array(
			'table' => "member",
			'alias' => "Member",
			'fields' => array(
				"Member.*",
			),
			'conditions' => array(
				"Member.id >" =>"2", 
			),
			'limit' => null,
			'offset' => null,
			'joins' => array(),
			'order' => null,
			'group' => null,
		)));
		var_dump($r);
		print "<hr/>";
		
		// DELETE
		DBI::load()->delete(array(
			'table' => "member",
			'alias' => "Member",
			'conditions' => array(
				"Member.name LIKE" =>"%_NEW",
			),
		));
*/

//-------------------------------------
// DBI実装
class DBI_Base extends DBI {

	protected $name ="";
	protected $ds =null;
	
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
		
		$this->ds =ConnectionManager::getInstance()
				->create($this->name,$connect_info);
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
	// テーブル構造解析
	public function desc ($table_name) {
		
		return $this->ds->describe($table_name);
	}
	
	//-------------------------------------
	// LAST_INSERT_IDの取得
	public function last_insert_id ($table_name, $pkey_name) {
		
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
			$command="execute", 
			$report_context=array()) {
		
		// Resq2互換のための仕様
		if (is_object($st)) {
			
			report_warning("SQL classes is old-library.",array(
				"object" =>$st,
			));
			
			$st =$st->__toString();
			$st =preg_replace('!\n!',"\n",$st);
		}
		
		$start_time =microtime(true);
		
		$result =$this->ds->$command($st);
		
		report('Execute Statement',array_merge($report_context,array(
			"Statement" =>$st,
			"Command" =>$command,
			"Elapsed" =>round((microtime(true) - $start_time)*1000,2)."ms",
			"NumRows" =>$this->ds->lastNumRows()."/".$this->ds->lastAffected(),
		)));
		
		if ($this->ds->error) {
			
			report_error('SQL Error',array(
				"Statement" =>$st,
				"Error" =>$this->ds->error,
			));
		}
		
		// 階層構造の変更（$a[Alias][Key] => $a[Alias.Key]）
		if ($command == "fetchRow") {
		
			$result_copy =array();
			
			foreach ((array)$result as $k1 => $v1) {
				
				foreach ((array)$v1 as $k2 => $v2) {
					
					$result_copy[$k1.".".$k2] =& $result[$k1][$k2];
				}
			}
			
			$result =$result_copy;
		}
		
		// 階層構造の変更（$a[n][Alias][Key] => $a[n][Alias.Key]）
		if ($command == "fetchAll") {
			
			$result_copy =array();
			
			foreach ((array)$result as $k1 => $v1) {
				
				foreach ((array)$v1 as $k2 => $v2) {
					
					foreach ((array)$v2 as $k3 => $v3) {
						
						$result_copy[$k1][$k2.".".$k3] =& $result[$k1][$k2][$k3];
					}
				}
			}
			
			$result =$result_copy;
		}
		
		return $result;
	}
	
	//-------------------------------------
	// Query実行(全件取得)
	public function select ($query) {
	
		$st =$this->st_select($query);
		$result =$this->exec($st,"fetchAll",array(
			"Type" =>"select",
			"Query" =>$query,
		));
		return $result;
	}
	
	//-------------------------------------
	// Query実行(1件のデータ取得)
	public function select_one ($query) {
	
		$st =$this->st_select($query);
		$result =$this->exec($st,"fetchRow",array(
			"Type" =>"select_one",
			"Query" =>$query,
		));
		return $result;
	}
	
	//-------------------------------------
	// Query実行(件数取得)
	public function select_count ($query) {
		
		$query["fields"] =array("COUNT(*) AS count");
		
		$st =$this->st_select($query);
		$result =$this->exec($st,"fetchRow",array(
			"Type" =>"select_count",
			"Query" =>$query,
		));
		
		$count =(int)$result["count"];
		
		return $count;
	}
	
	//-------------------------------------
	// Query実行(Pager取得)
	public function select_pager ($query) {
		
		$query["fields"] =array("COUNT(*) AS count");
		
		$offset =$query["offset"];
		$limit =$query["limit"];
		$slider =$query["slider"];
		
		unset($query["offset"]);
		unset($query["limit"]);
		unset($query["slider"]);
		
		$st =$this->st_select($query);
		$result =$this->exec($st,"fetchRow",array(
			"Type" =>"select_pager",
			"Query" =>$query,
		));
		
		$count =(int)$result["0.count"];
		$pager =$limit
				? $this->build_pager($offset,$limit,$count,$slider)
				: null;
		
		return $pager;
	}
	
	//-------------------------------------
	// Query実行(UPDATE)
	public function update ($query) {
	
		$st =$this->st_update($query);
		$result =$this->exec($st,"execute",array(
			"Type" =>"update",
			"Query" =>$query,
		));
		return $result;
	}
	
	//-------------------------------------
	// Query実行(INSERT)
	public function insert ($query) {
	
		$st =$this->st_insert($query);
		$result =$this->exec($st,"execute",array(
			"Type" =>"insert",
			"Query" =>$query,
		));
		return $result;
	}
	
	//-------------------------------------
	// Query実行(conditionsの有無によりINSERTまたはUPDATE)
	public function save ($query) {
		
		if ($query["conditions"]) {
			
			return $this->update($query);
			
		} else {
		
			return $this->insert($query);
		}
	}
	
	//-------------------------------------
	// Query実行(DELETE)
	public function delete ($query) {
	
		$st =$this->st_delete($query);
		$result =$this->exec($st,"execute",array(
			"Type" =>"delete",
			"Query" =>$query,
		));
		return $result;
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
		
		// conditions
		$query["conditions"] =$this->ds->conditions($query["conditions"],true,false);
		
		$st =$this->ds->buildStatement($query,$model=null);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（UPDATE）
	public function st_update ($query) {
		
		$default_query =array(
			'fields' => array(),
			'conditions' => array(),
			'table' => null,
			'alias' => $query["table"],
			'joins' => null,
		);
		$query =array_merge($default_query,$query);
		
		// fields
		$update_fields =array();
		
		foreach ($query["fields"] as $k => $v) {
			
			if ($v === null) {
				
				$update_fields[] =$this->ds->name($k)." = NULL"; 
				continue; 
			}
			
			$update_fields[] =$this->ds->name($k)
					." = ".$this->ds->value($v, "string", false);
		}
		
		$query["fields"] =implode(", ",$update_fields);
		
		// conditions
		$query["conditions"] =$this->ds->conditions($query["conditions"]);
		
		$st =$this->ds->renderStatement('update',$query);
		
		return $st;
	}
	
	//-------------------------------------
	// SQL組み立て（INSERT）
	public function st_insert ($query) {
		
		$default_query =array(
			'table' => null,
			'fields' => array(),
			'alias' => $query["table"],
		);
		$query =array_merge($default_query,$query);
		
		// fields, values
		$insert_fields =array();
		$insert_values =array();
		
		foreach ($query["fields"] as $k => $v) {
		
			if (strpos($k,".") !== false) {
				
				if (strlen($query["alias"])
						&& preg_match('!^'.$query["alias"].'\.([^\.]+)$!',$k,$match)) {
					
					$k =$match[1];
					
				} else {
				
					continue;
				}
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
	// SQL組み立て（DELETE）
	public function st_delete ($query) {
		
		$default_query =array(
			'table' => null,
			'alias' => $query["table"],
			'conditions' => array(),
		);
		$query =array_merge($default_query,$query);
		
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
	public function build_pager ($offset, $length, $total ,$slider=0) {
		
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
			
		for ($i=$pager['current']-$slider; $i<$pager['current']+$slider+1; $i++) {
			
			if (isset ($pager['pages'][$i])) {
			
				$pager['pages_slider'][$i] =$pager['pages'][$i];
			}
		}
		
		$pager['slider_prev'] =isset($pager['pages'][$pager['current']-$slider-1])
				? $pager['pages'][$pager['current']-$slider-1]
				: null;
		
		$pager['slider_next'] =isset($pager['pages'][$pager['current']+$slider+1])
				? $pager['pages'][$pager['current']+$slider+1]
				: null;
				
		return $pager;
	}
}
