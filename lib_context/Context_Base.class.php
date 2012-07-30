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
	public function & ref_session ($name) {
	
		return ref_array($this->session,$name);
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
					
					$rules[$group_name.".".$index.".".$k] =$v;
				}
			}
		}
		
		// Requiredチェック
		foreach ($required as $key) {
			
			$value =$this->input($key);
			
			$is_empty =is_array($value)
					? ! strlen(implode('',$value))
					: ! strlen($value);
					
			if ($is_empty) {
				
				if ($errmsg_label =label("errmsg.input.required.".$key)) {
				
					$error =$errmsg_label;
					
				} elseif ($col_label =label("cols.".$key)) {
				
					$error =$col_label." : 必ず入力してください";
					
				} else {
				
					$error =$key." : 必ず入力してください";
				}
				
				$this->errors($key.'.required',$error);
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
				
				$this->errors($key.'.'.$rule["type"],$error);
			}
		}
	}
}
