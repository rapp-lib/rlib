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
			$key =$input[(string)$setting["sort_param_name"]];
			$value =$setting["map"][$key];
			
			if ($value) {
			
				$query["order"] =$value;
			
			} elseif ($setting["default"]) {
			
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
			
			unset($query["paging"]);
		}
		
		return $query;
	}

	//-------------------------------------
	// 配列形式のカラムをunserialize
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
			
			$gts[$t[$key]][$index] =$reduce_key
					? $t[$reduce_key]
					: $t;
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
	// 指定した列の値で配列を得る
	public function convert_to_hashlist (
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

	//-------------------------------------
	// 親要素への変更を子要素へ反映
	public function affect_assoc (
			$id, 
			$values=null,
			$options=array()) {
		
		$ds =$options["ds"];
		$table =$options["table"];
		$parent_key =$options["parent_key"];
		$child_key =$options["child_key"];
		
		// 子要素を全て削除
		$query =array(
			"table" =>$table,
			"conditions" =>array(
				$parent_key =>$id,
			),
		);
		dbi($ds)->delete($query);
		
		// 子要素の登録
		foreach ($values as $value) {
			
			if ( ! $value) {
				
				continue;
			}
			
			// valueは値として、指定の列に登録（Bridge）
			if ($child_key) {
			
				$fields =array(
					$parent_key =>$id,
					$child_key =>$value,
				);
			
			// valueは値のセットとして、すべて登録（HasMany）
			} else {
				
				$fields =$value;
				$fields[$parent_key] =$id;
			}
			
			$query =array(
				"table" =>$table,
				"fields" =>$fields, 
			);
			dbi($ds)->insert($query);
		}
	}

	//-------------------------------------
	// 親要素に関連する子要素を取り込む
	public function merge_assoc (
			& $ts,
			$query=array()) {
		
		if ( ! $ts) {
			
			return;
		}
		
		if ( ! is_numeric(array_pop(array_keys($ts)))) {
			
			return $this->merge_assoc($ts_copy =array(0=> & $ts),$query);
		}
		
		$ds =$query["ds"];
		$parent_key =$query["parent_key"];
		$child_key =$query["child_key"];
		$parent_key_origin =$query["parent_key_origin"];
		$children_key_origin =$query["children_key_origin"];
		unset($query["ds"]);
		unset($query["parent_key"]);
		unset($query["child_key"]);
		unset($query["parent_key_origin"]);
		unset($query["children_key_origin"]);
		
		$parent_ids =$this->convert_to_hashlist(
				$ts,
				$parent_key_origin);
				
		$query["conditions"][] =array($parent_key =>$parent_ids);
		
		// Bridgeの取得
		$ts_bridge =dbi($ds)->select($query);
		
		$this->merge_grouped_children(
				$ts, 
				$ts_bridge,
				$parent_key,
				$parent_key_origin, 
				$children_key_origin,
				$child_key);
	}

	//-------------------------------------
	// AssertSegmentの関連付け
	public function bind_segment ($segment_name, $segment_id) {
		
		$bound_segments =& ref_globals("bound_segments");
		
		$bound_segments[$segment_name] =$segment_id;
	}
	
	//-------------------------------------
	// Assert異常チェック
	public function assert ($assert_name, $assert_id) {
		
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
	// Assert異常時の処理
	public function assert_error ($params=array()) {
		
		report_warning("Assert Error",$params);
	}
}