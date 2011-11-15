<?php

//-------------------------------------
// 
class Model_Base extends Model {
	
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
	// 配列形式のカラムをunserialize
	public function unserialize_fields ($ts, $field_names) {
		
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
		
		return $ts;
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
	// 特定要素でのグルーピング
	public function group_by ($ts, $key) {
		
		$gts =array();
		
		foreach ($ts as $index => $t) {
			
			$gts[$t[$key]][$index] =$t;
		}
		
		return $gts;
	}
	
	//-------------------------------------
	// 下位の要素の統合
	public function merge_children (
			$ts, 
			$children, 
			$key, 
			$children_name="children") {
			
		foreach ($ts as $index => $t) {
			
			$ts[$index][$children_name] =$children[$t[$key]];
		}
		
		return $ts;
	}
		
	//-------------------------------------
	// 下位要素を特定要素でのグルーピングして統合
	public function merge_grouped_children (
			$ts1, 
			$ts2,
			$parent_key,
			$child_key, 
			$children_name="children") {
		
		$gts2 =$this->group_by($ts2,$parent_key);
		$ts1 =$this->merge_children($ts1,$gts2,$child_key,$children_name);
		
		return $ts1;
	}
		
	//-------------------------------------
	// 指定した列の値でKV配列を得る
	public function convert_to_hashlist (
			$ts,
			$key_name,
			$value_name) {
		
		$list =array();
		
		foreach ($ts as $t) {
			
			$list[$t[$key_name]] =$t[$value_name];
		}
		
		return $list;
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
}