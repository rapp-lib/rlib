<?php

//-------------------------------------
// 
class Model_Base extends Model {

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
}