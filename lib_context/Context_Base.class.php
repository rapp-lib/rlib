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
	// セッション識別子を取得
	public function get_sname () {
		
		return $this->sname;
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
	    
		return array_registry($this->session["__input"],$name,$value,array(
            "escape" =>true,
            "no_array_merge" =>true,
        ));
	}
	
	//-------------------------------------
	// 
	public function errors ($name=null, $value=null) {
	
		return array_registry($this->session["__errors"],$name,$value,array(
            "escape" =>true,
            "no_array_merge" =>true,
        ));
	}
	
	//-------------------------------------
	// 
	public function session ($name=null, $value=null) {
	
		return array_registry($this->session,$name,$value,array(
            "escape" =>true,
            "no_array_merge" =>true,
        ));
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
			$options=array(),
			$group_name=null) {
			
		$errors =$this->errors();
		
		$rules =array_merge(
				(array)registry("Validate.rules"),
				(array)$extra_rules);
		
		// c[Group.X.Table.col]形式の入力値の要素を適用対象とする
		if ($group_name) {
			
			$grouped_indeses =array();
			
			foreach ($this->input() as $k=>$v) {
				
				list($target_group_name,$target_index,) =explode(".",$k,3);
				
				if ($target_group_name == $group_name) {
					
					$grouped_indeses[$target_index] =$target_index;
				}
			}
			
			$required_copy =$required;
			$required =array();
			
			$rules_copy =$rules;
			$rules =array();
			
			foreach ($grouped_indeses as $index) {
				
				// requiredの範囲拡張
				foreach ($required_copy as $v) {
					
					$required[] =$group_name.".".$index.".".$v;
				}
				
				// rulesの範囲拡張
				foreach ($rules_copy as $k => $v) {
					$v["target"] =$group_name.".".$index.".".$v["target"];
					$rules[] =$v;
				}
			}
		}
		
		// Requiredチェック
		foreach ($required as $key) {
			
			$value =$this->input($key,null,fase);
			
			$module =load_module("rule","required",true);
			$result =call_user_func_array($module,array(
				$value,
				null,
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
				
				$errors[$key.'.required'] =$error;
			
            } else {
                
                unset($errors[$key.'.required']);
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
			$value =$this->input($key,null,fase);
			
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
					
				} elseif ($errmsg_label =label("errmsg.input.".$rule["type"].".".$key)) {
				
					$error =$errmsg_label;
					
				} elseif ($col_label =label("cols.".$key)) {
				
					$error =$col_label." : ".$result;
					
				} else {
				
					$error =$result;
				}
				
				$errors[$key.'.'.$rule["type"]] =$error;
                
			} else {
                
                unset($errors[$key.'.'.$rule["type"]]);
            }
		}
		
		// c[Group.X.Table.col]形式の入力値のエラーをerrors[Group][X]に分解する
		if ($group_name) {
			
			foreach ($errors as $k=>$v) {
				
				list($error_group_name, $error_index, $error_k) =explode(".",$k,3);
				
				if ($error_group_name == $group_name) {
					
					unset($errors[$k]);
					
					$errors[$error_group_name][$error_index][$error_k] =$v;
				}
			}
		}
		
		$this->errors(false,false);
		$this->errors($errors);
	}
}
