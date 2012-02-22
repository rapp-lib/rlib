<?php

//-------------------------------------
//
class Context_Base {

	protected $id =null;
	protected $sname =null;
	protected $session =array();
	
	//-------------------------------------
	// report出力時の値セット
	public function __report () {
		
		return array(
			"sname" =>$this->sname,
			"session" =>$this->session,
		);
	}
	
	//-------------------------------------
	// 
	public function bind_session ($sname) {
	
		$this->sname =$sname;
		
		if ($sname) {
		
			$session_root =& ref_session("context");
			$this->session =& $session_root[$sname];
			
			if ( ! is_array($this->session)) {
			
				$this->session =array();
			}
		}
	}
	
	//-------------------------------------
	// 
	public function id ($id=null) {
	
		if ($id !== null) {
		
			$this->session["__id"] =$id;
		}
		
		return $this->session["__id"];
	}
	
	//-------------------------------------
	// 
	public function input ($name=null, $value=null) {
	
		return array_registry($this->session["__input"],$name,$value,true);
	}
	
	//-------------------------------------
	// 
	public function errors ($name=null, $value=null) {
	
		return array_registry($this->session["__errors"],$name,$value,true);
	}
	
	//-------------------------------------
	// 
	public function session ($name=null, $value=null) {
	
		return array_registry($this->session,$name,$value);
	}
	
	//-------------------------------------
	// 
	public function get_fields ($fields) {
	
		// fields補完
		foreach ($fields as $k => $v) {
			
			if (is_numeric($k) && ! preg_match('![^a-zA-Z0-9\._]!',$v)) {
				
				$fields[$v] =$this->input((string)$v);
				unset($fields[$k]);
			}
		}
		
		return $fields;
	}
	
	//-------------------------------------
	// 入力値のチェックロジックの実効
	public function validate (
			$required=array(), 
			$extra_rules=array(),
			$groups=array()) {
			
		$this->errors(false,false);
		
		$rules =array_merge(
				(array)registry("Validate.rules"),
				(array)$extra_rules);
		
		// Group.X.Table.col形式の入力値の要素を適用対象とする
		foreach ($groups as $group_name) {
			
			$grouped_input =(array)$this->input($group_name);
			
			foreach (array_keys($grouped_input) as $index) {
				
				// requiredの範囲拡張
				foreach ($required_copy=$required as $v) {
					
					$required[] =$group_name.".".$index.".".$v;
				}
				
				// rulesの範囲拡張
				foreach ($rules_copy=$rules as $k => $v) {
					
					$required[$group_name.".".$index.".".$k] =$v;
				}
			}
		}
		
		// Requiredチェック
		foreach ($required as $key) {
			
			$value =$this->input($key);
			
			if ( ! strlen($value)) {
				
				if ($errmsg_label =label("errmsg.input.required.".$key)) {
				
					$error =$errmsg_label;
					
				} elseif ($col_label =label("cols.".$key)) {
				
					$error =$col_label." : 必ず入力してください";
					
				} else {
				
					$error =$key." : 必ず入力してください";
				}
				
				$this->errors($key,$error);
			}
		}
		
		// その他のチェック
		foreach ($rules as $rule) {
			
			if ( ! $rule["target"] || ! $rule["type"]) {
				
				report_error("Rule is-not valid.",array(
					"type" =>$rule["type"],
					"target" =>$rule["target"],
					"option" =>$rule["option"],
					"message" =>$rule["message"],
				));
			}
			
			$key =$rule["target"];
			$value =$this->input($key);
			
			// 必須チェックエラー等既にエラー判定の項目はスキップ
			if ($this->errors($key)) {
				
				continue;
			}
			
			$module =load_module("rule",$rule["type"],true);
			$result =call_user_func_array($module,array(
				$value,
				$rule["option"],
				$key, 
				$this,
			));
				
			if ($result) {
				
				if ($rule["message"]) {
				
					$error =$rule["message"];
					
				} elseif ($errmsg_label =label("errmsg.input.required.".$key)) {
				
					$error =$errmsg_label;
					
				} elseif ($col_label =label("cols.".$key)) {
				
					$error =$col_label." : ".$result;
					
				} else {
				
					$error =$result;
				}
				
				$this->errors($key,$error);
			}
		}
	}
	
	//-------------------------------------
	// <DEPLICATED 110629> Model::get_list_queryを使用してください
	public function query_list ($query=array()) {
	
		if ($query["search"]) {
		
			foreach ($query["search"] as $name => $setting) {
			
				$targets =is_array($setting["target"])
						? $setting["target"]
						: array($setting["target"]);
						
				$part_queries =array();
				
				foreach ($targets as $target) {
				
					$module =load_module("search_type",$setting["type"],true);
					$part_query =call_user_func_array($module,array(
						$name,
						$target,
						$this->input((string)$name),
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
			
			unset($query["search"]);
		}
		
		if ($query["sort"]) {
		
			$setting =$query["sort"];
			$key =$this->input((string)$setting["sort_param_name"]);
			$value =$setting["map"][$key];
			
			if ($value) {
			
				$query["order"] =$value;
			
			} elseif ($setting["default"]) {
			
				$query["order"] =$setting["default"];
			}
			
			unset($query["sort"]);
		}
		
		if ($query["paging"]) {
		
			$setting =$query["paging"];
			
			if ($setting["offset_param_name"]
					&& is_numeric($this->input((string)$setting["offset_param_name"]))) {
					
				$query["offset"] =(int)$this->input((string)$setting["offset_param_name"]);
			}
			
			if ($setting["limit_param_name"]
					&& is_numeric($this->input((string)$setting["limit_param_name"]))) {
					
				$query["limit"] =(int)$this->input((string)$setting["limit_param_name"]);
			
			} elseif ($setting["limit"]) {
				
				$query["limit"] =(int)$setting["limit"];
			}
			
			if ($setting["slider"]) {
			
				$query["paging_slider"] =$setting["slider"];
			}
			
			unset($query["paging"]);
		}
		
		return $query;
	}
}
