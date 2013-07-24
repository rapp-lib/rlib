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
	// [DEPLECATED] 配列形式のカラムをunserialize
	public function unserialize_fields_DELETE (
			& $ts, 
			$field_names) {
		
		$field_names =is_array($field_names)
				? $field_names
				: array($field_names);
				
		foreach ($field_names as $field_name) {
			
			if (isset($ts[$field_names])) {
			
				$ts[$field_names] =unserialize($ts);
			
			} else {
			
				foreach ($ts as $t_index => $t) {
				
					if (isset($ts[$t_index][$field_names])) {
						
						$ts[$t_index][$field_names] =unserialize($t);
					}
				}
			}
		}
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
	// Assoc処理
	//

	//-------------------------------------
	// 親要素への変更を子要素へ反映
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
			
			if ( ! strlen($value)) {
				
				continue;
			}
			
			$query["fields"] =$base_fields;
			$query["fields"][$parent_key] =$id;
			$query["fields"][$child_key] =$value;
			
			dbi($ds)->insert($query);
		}
	}

	//-------------------------------------
	// 親要素に関連する子要素を取り込む
	public function merge_assoc (
			& $ts,
			$query=array()) {
			
		/*
			■呼び出しサンプル：
		
		     // Customerに関連するRequirementのIDをrequirement_idsとして組入
		     model()->merge_assoc($ts,array(
		          // SELECT発行
		          "table" =>"Requirement",
		          // 対象の中での親へのFK
		          "parent_key" =>"Requirement.customer_id",
		          // tsのpk（対象のFKと結合する際に使用する）
		          "parent_key_origin" =>"Customer.id",
		          // 対象のtsへ組み入れ先の擬似カラム名
		          "children_name" =>"Customer.requirement_ids",
		          // （任意）対象をhashlistに変換する場合のカラム名
		          "child_key" =>"Requirement.id",
		     ));
		*/
		
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
	
	//--------------------------------------------------------------------------
	// [DEPLECATED] Assert処理 
	//

	//-------------------------------------
	// [DEPLECATED] AssertSegmentの関連付け
	public function bind_segment_DELETE ($segment_name, $segment_id) {
		
		$bound_segments =& ref_globals("bound_segments");
		
		$bound_segments[$segment_name] =$segment_id;
	}
	
	//-------------------------------------
	// [DEPLECATED] Assert異常チェック
	public function assert_DELETE ($assert_name, $assert_id) {
		
		// Segment異常チェック
		$bound_segments =& ref_globals("bound_segments");
		
		foreach ((array)$bound_segments as $segment_name => $segment_id) {
			
			$assert_config =registry("Assert.".$assert_name.'.segment.'.$segment_name);
			
			if ( ! $assert_config) {
				
				continue;
			}
			
			// SQLを発行してデータの検出を確認
			if ($assert_config["query"]) {
			
				dbi()->bind(array(
					"segment" =>$segment_id,
					"assert" =>$assert_id,
				));
				$exists =dbi()->select_count($assert_config["query"]);
				
				if ( ! $exists) {
					
					$this->assert_error(array(
						"assert_config" =>$assert_config,
						"segment_name" =>$segment_name,
						"segment_id" =>$segment_id,
						"assert_name" =>$assert_name,
						"assert_id" =>$assert_id,
						"exists" =>$exists,
					));
				}
			}
		}
	}
	
	//-------------------------------------
	// [DEPLECATED] Assert異常時の処理
	public function assert_error_DELETE ($params=array()) {
		
		report_warning("Assert Error",$params);
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
	// SELECTの後処理（tsを対象）
	public function after_read ( & $ts, & $query) {
	}
	
	//-------------------------------------
	// INSERT/UPDATE/DELETEの後処理（idを対象）
	public function after_write ( & $id, & $query) {
	}
	
	//-------------------------------------
	// SELECTの後処理
	public function after_select ( & $ts, & $query) {
		
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
	
	//-------------------------------------
	// Query実行(全件取得)
	public function select ($query) {
		
		$this->before_select($query);
		
		$ts =dbi()->select($query);
		
		$this->after_select($ts,$query);
		
		return $ts;
	}
	
	//-------------------------------------
	// Query実行(SELECTクエリ発行のみ)
	public function select_nofetch ($query) {
		
		$this->before_select($query);
		
		$res =dbi()->select_nofetch($query);
		
		return $res;
	}
	
	//-------------------------------------
	// Query実行(クエリ発行結果のFetch)
	public function fetch ($res, $query) {
		
		$t =dbi()->fetch($res);
		
		$this->after_select($ts=array(& $t),$query);
		
		return $t;
	}
	
	//-------------------------------------
	// Query実行(1件のデータ取得)
	public function select_one ($query) {
		
		$this->before_select($query);
		
		$t =dbi()->select_one($query);
		
		$this->after_select($ts=array(& $t),$query);
		
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
}