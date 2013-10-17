<?php

//-------------------------------------
// 
class Model_Base {
	
	protected static $instance =array();
	
	//-------------------------------------
	// インスタンスのファクトリ
	public static function load ($name=null) {
		
		$name =$name
				? $name."Model"
				: "Model_App";
		
		if ( ! self::$instance[$name]) {
			
			self::$instance[$name] =new $name;
		}
		
		return self::$instance[$name];
	}
	
	//-------------------------------------
	// クエリの統合（上書きを避けつつ右を優先）
	public function merge_query ($query1, $query2) {
		
		$args =func_get_args();
		$query1 =array_shift($args);
		
		foreach ($args as $query2) {
		
			foreach ($query2 as $k => $v) {
				
				// 配列ならば要素毎に追加
				if (is_array($v)) {
					
					foreach ($v as $v_k => $v_v) {
						
						// 数値添え字ならば最後に追加
						if (is_numeric($v_k)) {
						
							$query1[$k][] =$v_v;
						
						// 連想配列ならば要素の上書き
						} else {
							
							$query1[$k][$v_k] =$v_v;
						}
					}
				
				// スカラならば上書き
				} else {
				
					$query1[$k] =$v;
				}
			}
		}
		
		return $query1;
	}
		
	//-------------------------------------
	// 検索整列ページングを行うクエリを生成
	public function get_list_query ($list_setting, $input) {
	
		$query =array();
		
		// 検索条件の指定
		if ($list_setting["search"]) {
		
			foreach ($list_setting["search"] as $name => $setting) {
			
				$targets =is_array($setting["target"])
						? $setting["target"]
						: array($setting["target"]);
						
				$part_queries =array();
				
				foreach ($targets as $target) {
				
					$module =load_module("search_type",$setting["type"],true);
					$part_query =call_user_func_array($module,array(
						$name,
						$target,
						$input[(string)$name],
						$setting,
						$this,
					));
					
					if ($part_query) {
					
						$part_queries[] =$part_query;
						
						if ($setting["merge_query"]) {
							
							$query =$this->merge_query($query,$setting["merge_query"]);
						}
					}
				}
				
				if (count($part_queries) == 1) {
				
					$query["conditions"][] =$part_queries[0];
					
				} elseif (count($part_queries) > 1) {
				
					$query["conditions"][] =array("or" =>$part_queries);
				}
			}
		}
		
		// 整列条件の指定
		if ($list_setting["sort"]) {
		
			$setting =$list_setting["sort"];
			$keys =$input[(string)$setting["sort_param_name"]];
			
			// 単数、複数設定可能
			if ( ! is_array($keys)) {
			
				$keys =array($keys);
			}
			
			ksort($keys);
			
			foreach ($keys as $key) {
			
				$value =$setting["map"][$key];
				
				if ($value) {
				
					$query["order"][] =$value;
				} 
			}
			
			// 設定されれていない場合
			if ( ! $query["order"] && $setting["default"]) {
			
				$query["order"] =$setting["default"];
			}
				
			unset($query["sort"]);
		}
		
		// ページング設定
		if ($list_setting["paging"]) {
		
			$setting =$list_setting["paging"];
			
			if ($setting["offset_param_name"]
					&& is_numeric($input[(string)$setting["offset_param_name"]])) {
					
				$query["offset"] =(int)$input[(string)$setting["offset_param_name"]];
			}
			
			if ($setting["limit_param_name"]
					&& is_numeric($input[(string)$setting["limit_param_name"]])) {
					
				$query["limit"] =(int)$input[(string)$setting["limit_param_name"]];
			
			} elseif ($setting["limit"]) {
				
				$query["limit"] =(int)$setting["limit"];
			}
			
			if ($setting["slider"]) {
				
				$query["paging_slider"] =(int)$setting["slider"];
			}
			
			unset($query["paging"]);
		}
		
		return $query;
	}
		
	//-------------------------------------
	// 特定要素でのグルーピング
	public function group_by (
			& $ts, 
			$key,
			$reduce_key=null) {
		
		$gts =array();
		
		foreach ($ts as $index => $t) {
			
			if ($reduce_key) {
			
				$gts[$t[$key]][$t[$reduce_key]] =$t[$reduce_key];
				
			} else {
			
				$gts[$t[$key]][$index] =$t;
			}
		}
		
		return $gts;
	}
	
	//-------------------------------------
	// 下位の要素の統合
	public function merge_children (
			& $ts, 
			$children, 
			$key, 
			$children_name="children") {
			
		foreach ($ts as $index => $t) {
			
			$ts[$index][$children_name] =$children[$t[$key]];
		}
	}
		
	//-------------------------------------
	// 下位要素を特定要素でグルーピングして統合
	public function merge_grouped_children (
			& $ts1, 
			$ts2,
			$parent_key,
			$child_key, 
			$children_name,
			$reduce_key=null) {
		
		$gts2 =$this->group_by($ts2,$parent_key,$reduce_key);
		$this->merge_children($ts1,$gts2,$child_key,$children_name);
	}
		
	//-------------------------------------
	// [DEPLECATED]指定した列の値で配列を得る
	public function convert_to_hashlist_DELETE (
			& $ts,
			$key_name,
			$value_name=null) {
		
		return $this->hash($ts, $key_name, $value_name);
	}
		
	//-------------------------------------
	// 指定した列の値で配列を得る
	public function hash (
			& $ts,
			$key_name,
			$value_name=null) {
		
		$list =array();
		
		foreach ($ts as $t) {
			
			if ($value_name) {
			
				$list[$t[$key_name]] =$t[$value_name];
				
			} else {
			
				$list[] =$t[$key_name];
			}
		}
		
		return $list;
	}
	
	//--------------------------------------------------------------------------
	// Query代理実行
	//
	
	//-------------------------------------
	// Query実行(全件取得)
	public function select ($query) {
		
		$this->before_select($query);
		
		$ts =dbi()->select($query);
		
		$this->after_select($ts,$query);
		
		return $ts;
	}
	
	//-------------------------------------
	// Query実行(1件のデータ取得)
	public function select_one ($query) {
		
		$this->before_select($query);
		
		$t =dbi()->select_one($query);
				
		$this->after_select_one($t,$query);
		
		return $t;
	}
	
	//-------------------------------------
	// Query実行(件数取得)
	public function select_count ($query) {
		
		$this->before_select($query);
		
		$count =dbi()->select_count($query);
		
		return $count;
	}
	
	//-------------------------------------
	// Query実行(Pager取得)
	public function select_pager ($query) {
		
		$this->before_select($query);
		
		$p =dbi()->select_pager($query);
		
		return $p;
	}
	
	//-------------------------------------
	// Query実行(SELECTクエリ発行のみ)
	public function select_nofetch ($query) {
		
		$this->before_select($query);
		
		$result =dbi()->select_nofetch($query);
		
		return new Model_ResultBuffer($this,$result,$query);
	}
	
	//-------------------------------------
	// Query実行(クエリ発行結果のFetch)
	public function fetch ($result, $query) {
		
		$t =dbi()->fetch($result);
				
		$this->after_select_one($t,$query);
		
		return $t;
	}
	
	//-------------------------------------
	// Query実行(INSERT)
	public function insert ($query) {
		
		$this->before_insert($query);
		
		$id =dbi()->insert($query)
				? dbi()->last_insert_id() 
				: null;
		
		$this->after_insert($id, $query);
		
		return $id;
	}
	
	//-------------------------------------
	// Query実行(UPDATE)
	public function update ($query, $id) {
		
		$this->before_update($id, $query);
		
		$r =dbi()->update($query);
		
		$this->after_update($id, $query);
		
		return $r;
	}
	
	//-------------------------------------
	// Query実行(DELETE)
	public function delete ($query, $id) {
		
		$this->before_delete($id, $query);
		
		$r =dbi()->delete($query);
		
		$this->before_delete($id, $query);
		
		return $r;
	}
	
	//--------------------------------------------------------------------------
	// SQL前後処理
	//
	
	//-------------------------------------
	// SELECT/DELETE/UPDATEの前処理（table,conditionsを対象）
	public function before_read ( & $query) {
	}
	
	//-------------------------------------
	// INSERT/UPDATEの前処理（table,fieldsを対象）
	public function before_write ( & $query) {
	
		$this->extend_fields_splice($query);
	}
	
	//-------------------------------------
	// SELECTの後処理（tsを対象）
	public function after_read ( & $ts, & $query) {
		
		$this->extend_fields_merge($ts,$query);
	}
	
	//-------------------------------------
	// INSERT/UPDATE/DELETEの後処理（idを対象）
	public function after_write ( & $id, & $query) {
	
		$this->extend_fields_affect($id,$query);
	}
	
	//-------------------------------------
	// SELECTの前処理
	public function before_select ( & $query) {
		
		$this->before_read($query);
	}
	
	//-------------------------------------
	// INSERTの前処理
	public function before_insert ( & $query) {
		
		$this->before_write($query);
	}
	
	//-------------------------------------
	// UPDATEの前処理
	public function before_update ( & $id, & $query) {
		
		$this->before_read($query);
		$this->before_write($query);
	}
	
	//-------------------------------------
	// DELETEの前処理
	public function before_delete ( & $id, & $query) {
		
		$this->before_read($query);
	}
	
	//-------------------------------------
	// SELECTの後処理
	public function after_select ( & $ts, & $query) {
		
		$this->after_read($ts,$query);
	}
	
	//-------------------------------------
	// SELECT(単一行取得)の後処理
	public function after_select_one ( & $t, & $query) {
		
		$ts =$t
				? array(& $t)
				: array();
				
		$this->after_read($ts,$query);
	}
	
	//-------------------------------------
	// INSERTの後処理
	public function after_insert ( & $id, & $query) {
		
		$this->after_write($id,$query);
	}
	
	//-------------------------------------
	// UPDATEの後処理
	public function after_update ( & $id, & $query) {
		
		$this->after_write($id,$query);
	}
	
	//-------------------------------------
	// DELETEの後処理
	public function after_delete ( & $id, & $query) {
		
		$this->after_write($id,$query);
	}
	
	//--------------------------------------------------------------------------
	// fields拡張処理
	//

	protected $spliced_fields =array();
	protected $meta_cols =array(
			"meta_name","idx","elm","value","value_int","value_date");
			
	//-------------------------------------
	// fields拡張/SELECT時の拡張要素の取得
	public function extend_fields_merge ( & $ts, & $query) {
		
		// Queryを参照して対象となるfields拡張設定を適用する
		foreach ((array)registry("Model.extends.fields") as $table => $cols) {
			
			if ($query["table"] != $table) {
				
				continue;
			}
			
			foreach ($cols as $col => $info) {
				
				$target_col =$table.".".$col;
				
				// auto_loadがtrueの場合fieldsに明示されなくても取得
				if ( ! $info["auto_load"]
						&& ! in_array($target_col,(array)$query["fields"])) {
					
					continue;
				}
				
				unset($info["auto_load"]);
				
				// KVSのmerge処理
				if ($info["type"]=="assoc_bridge") {
					
					// パラメータのリネーム
					$info["parent_key_origin"] =$info["parent_pk"];
					$info["parent_key"] =$info["connect_by"];
					$info["child_key"] =$info["reduce_by"];
					$info["children_name"] =$target_col;
					
					// Model::merge_assoc
					$query_sub =$info;
					unset($query_sub["connect_by"]);
					unset($query_sub["reduce_by"]);
					unset($query_sub["type"]);
					unset($query_sub["auto_load"]);
					unset($query_sub["col_name"]);
					unset($query_sub["parent_pk"]);
					model()->merge_assoc($ts,$query_sub);
				
				// MetaKVSのmerge処理
				} elseif ($info["type"]=="assoc_meta") {
					
					// col_nameのリネーム
					foreach ($this->meta_cols as $k=>$v) {
						
						if ( ! $info["col_name"][$k]) {
							
							$info["col_name"][$k] =$k;
						}
					}
					
					// meta_nameによる絞り込み
					$info["conditions"][] =array(
						$info["table"].".".$info["col_name"]["meta_name"] =>$target_col,
					);
					
					// パラメータのリネーム
					$info["parent_key_origin"] =$info["parent_pk"];
					$info["parent_key"] =$info["connect_by"];
					$info["children_name"] =$target_col;
					
					// Model::merge_assoc
					$query_sub =$info;
					unset($query_sub["connect_by"]);
					unset($query_sub["type"]);
					unset($query_sub["auto_load"]);
					unset($query_sub["col_name"]);
					unset($query_sub["parent_pk"]);
					model()->merge_assoc($ts,$query_sub);
					
					// Metadataの再構築
					foreach ($ts as $i=>$t) {
					
						$values_base =array();
						
						foreach ((array)$t[$target_col] as $meta_data) {
							
							$idx =$meta_data[$info["table"].".".$info["col_name"]["idx"]];
							$elm =$meta_data[$info["table"].".".$info["col_name"]["elm"]];
							$value =$meta_data[$info["table"].".".$info["col_name"]["value"]];
							
							$values_base[$idx][$elm] =$value;
						}
						
						$ts[$i][$target_col] =$values_base;
					}
				}
			}
		}
	}
	
	//-------------------------------------
	// fields拡張/save前の事前のSPLICE処理
	public function extend_fields_splice ( & $query) {
		
		// Queryを参照して対象となるfields拡張設定を適用する
		foreach ((array)registry("Model.extends.fields") as $table => $cols) {
			
			if ($query["table"] != $table) {
				
				continue;
			}
			
			foreach ($cols as $col => $info) {
				
				$target_col =$table.".".$col;
				
				if (isset($query["fields"][$target_col])) {
										
					// SPLICE処理
					$this->spliced_fields[$target_col] =$query["fields"][$target_col];
				}
				
				unset($query["fields"][$target_col]);
			}
		}
	}
	
	//-------------------------------------
	// fields拡張/更新処理時のAffect処理
	public function extend_fields_affect ( & $id, & $query) {
		
		// Queryを参照して対象となるfields拡張設定を適用する
		foreach ((array)registry("Model.extends.fields") as $table => $cols) {
			
			if ($query["table"] != $table) {
				
				continue;
			}
			
			foreach ($cols as $col => $info) {
				
				$target_col =$table.".".$col;
				
				if ( ! isset($this->spliced_fields[$target_col])) {
				
					continue;
				}
					
				$values_base =(array)$this->spliced_fields[$target_col];
				
				// KVSのmerge処理
				if ($info["type"]=="assoc_bridge") {
						
					// パラメータのリネーム
					$info["parent_key"] =$info["connect_by"];
					$info["child_key"] =$info["reduce_by"];
					
					// 外部テーブルへのAFFECT処理
					$query_sub =$info;
					unset($query_sub["connect_by"]);
					unset($query_sub["reduce_by"]);
					unset($query_sub["type"]);
					unset($query_sub["auto_load"]);
					unset($query_sub["col_name"]);
					unset($query_sub["parent_pk"]);
					$this->affect_assoc($values_base,$id,$query_sub);
				
				// MetaKVSのmerge処理
				} elseif ($info["type"]=="assoc_meta") {
					
					// col_nameのリネーム
					foreach ($this->meta_cols as $k=>$v) {
						
						if ( ! $info["col_name"][$k]) {
							
							$info["col_name"][$k] =$k;
						}
					}
					
					// MetaKVSとして格納できるように分解
					$values =array();
					
					foreach ((array)$values_base as $idx=>$elms) {
						
						foreach ((array)$elms as $elm=>$value) {
						
							$values[] =array(
								$info["table"].".".$info["col_name"]["meta_name"] =>$target_col,
								$info["table"].".".$info["col_name"]["idx"] =>$idx,
								$info["table"].".".$info["col_name"]["elm"] =>$elm,
								$info["table"].".".$info["col_name"]["value"] =>$value,
								$info["table"].".".$info["col_name"]["value_int"] =>(int)$value,
								$info["table"].".".$info["col_name"]["value_date"] =>strtotime($value),
							);
						}
					}
				
					// パラメータのリネーム
					$info["parent_key"] =$info["connect_by"];
					
					// 外部テーブルへのAFFECT処理
					$query_sub =$info;
					unset($query_sub["connect_by"]);
					unset($query_sub["type"]);
					unset($query_sub["auto_load"]);
					unset($query_sub["col_name"]);
					unset($query_sub["parent_pk"]);
					$this->affect_assoc($values,$id,$query_sub);
				}
			}
		}
	}

	//-------------------------------------
	// [private化予定]親要素に関連する子要素を取り込む
	public function merge_assoc (
			& $ts,
			$query=array()) {
		
		if ( ! $ts) {
			
			return;
		}
		
		if ( ! is_numeric(array_pop(array_keys($ts)))) {
			
			return $this->merge_assoc($ts_copy =array(0=> & $ts),$query);
		}
		
		// パラメータの抽出
		$ds =$query["ds"];
		$parent_key =$query["parent_key"];
		$child_key =$query["child_key"];
		$parent_key_origin =$query["parent_key_origin"];
		$children_name =$query["children_name"];
		unset($query["ds"]);
		unset($query["parent_key"]);
		unset($query["child_key"]);
		unset($query["parent_key_origin"]);
		unset($query["children_name"]);
		
		$parent_ids =$this->hash($ts, $parent_key_origin);
				
		// Bridgeの取得
		$query["conditions"] =(array)$query["conditions"];
		$query["conditions"][] =array($parent_key =>$parent_ids);
		
		// ReduceKeyの指定
		if ($child_key) {
		
			$query["fields"] =array(
				$parent_key,
				$child_key,
			);
		}
		
		$ts_bridge =dbi($ds)->select($query);
		
		// Bridgeの情報のマージ
		$this->merge_grouped_children(
				$ts, 
				$ts_bridge,
				$parent_key,
				$parent_key_origin, 
				$children_name,
				$child_key);
	}

	//-------------------------------------
	// [private化予定]親要素への変更を子要素へ反映
	public function affect_assoc (
			$values,
			$id,
			$query=array()) {
		
		// パラメータの抽出
		$ds =$query["ds"];
		$base_fields =(array)$query["fields"];
		$parent_key =$query["parent_key"];
		$child_key =$query["child_key"];
		unset($query["ds"]);
		unset($query["fields"]);
		unset($query["parent_key"]);
		unset($query["child_key"]);
		
		// 登録済みデータの削除
		$query["conditions"] =(array)$query["conditions"];
		$query["conditions"][$parent_key] =$id;
		
		dbi($ds)->delete($query);
		
		// Bridge要素の登録
		foreach ((array)$values as $value) {
			
			if ((is_string($value) && ! strlen($value))
					|| (is_array($value) && ! count($value))) {
				
				continue;
			}
			
			$query["fields"] =$base_fields;
			$query["fields"][$parent_key] =$id;
			
			if ($child_key!==null) {
			
				$query["fields"][$child_key] =$value;
				dbi($ds)->insert($query);
				
			} elseif (is_array($value)) {
				
				$query_tmp =$query;
				$query_tmp["fields"] =array_merge($query["fields"],$value);
				dbi($ds)->insert($query_tmp);
			}
		}
	}
}

//-------------------------------------
// select_nofetch結果セット
class Model_ResultBuffer implements Iterator {
	
	protected $cur;
	protected $pos;
	protected $valid;
	protected $model;
	protected $result;
	protected $query;
	
	//-------------------------------------
	// 初期化
	public function __construct ($model, $result, $query) {
	
		$this->pos =0;
		$this->valid =true;
		$this->model =$model;
		$this->result =$result;
		$this->query =$query;
	}

	//-------------------------------------
	// 次の1件の取得
	public function fetch () {
		
		$this->next();
		$t =$this->current();
		return $t;
	}

	//-------------------------------------
	// override: Iterator::rewind
	public function rewind () {
	}

	//-------------------------------------
	// override: Iterator::cur
	public function current () {
		
		return $this->cur;
	}

	//-------------------------------------
	// override: Iterator::key
	public function key () {
		
		return $this->pos;
	}

	//-------------------------------------
	// override: Iterator::next
	public function next () {
		
		$this->cur =$this->model->fetch($this->result, $this->query);
		
		if ($this->cur !== null) {
			
			$this->pos++;
		}
	}

	//-------------------------------------
	// override: Iterator::valid
	public function valid () {
		
		return $this->cur !== null;
	}
}
