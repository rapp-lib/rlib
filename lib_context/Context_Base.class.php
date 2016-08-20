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
	// 入力チェック
	public function validate (
			$required=array(), 
			$extra_rules=array(),
			$options=array(),
			$group_name=null) {
        
        // [Deprecated] 同時指定
        if ($group_name) {
            
            return $this->validate_each($group_name,$required,$extra_rules);
        }
        
        foreach ((array)$required as $target) {
            
            $rule =array("type"=>"required","target"=>$target);
            $this->apply_rule($rule);
        }
        
        foreach ((array)$extra_rules as $rule) {
            
            $this->apply_rule($rule);
        }
    }
	
	//-------------------------------------
	// c[Base][n][Table.col]形式の入力チェック
	public function validate_each (
            $group_name,
    		$required=array(), 
    		$extra_rules=array()) {
        
        // group_name以下を排他
        foreach ((array)$this->errors() as $error_index => $message) {
            
            if (strpos($error_index,$group_name.".")===0) {
                
                $this->errors($error_index,false);
            }
        }
        
        foreach ($this->input($group_name) as $i => $values) {
            
            foreach ((array)$required as $target) {
                
                $rule =array("type"=>"required","target"=>$target);
                $rule["input_name"] =$group_name.".".$i.".".$target;
                $rule["value"] =$values[$target];
                $this->apply_rule($rule);
            }
            
            foreach ((array)$extra_rules as $rule) {
                
                $rule["input_name"] =$group_name.".".$i.".".$rule["target"];
                $rule["value"] =$values[$rule["target"]];
                $this->apply_rule($rule);
            }
        }
	}
	
	//-------------------------------------
	// 入力値のチェックロジックの実効
	private function apply_rule ($rule) {
        
        $input_name =$rule["input_name"] ? $rule["input_name"] : $rule["target"];
        $error_index =$input_name.".".$rule["type"];
        $value =$rule["value"] ? $rule["value"] : $this->input($input_name);
        
        $module =load_module("rule",$rule["type"],true);
        $result =call_user_func_array($module,array(
            $value, $rule["option"], $rule["target"], $this,
        ));
        
        if ( ! $result) {
            
            $this->errors($error_index,false);
            
        } else if ($rule["message"]) {
                
            $this->errors($error_index,$rule["message"]);
            
        } else if ($col_label =label("cols.".$rule["target"])) {
            
            $this->errors($error_index,$col_label." : ".$result);
                
        } else {
            
            $this->errors($error_index,$result);
        }
    }

	/**
	 * 値の消去
	 */
	public function clear () 
	{
		$this->session(false,false);
	}

	/**
	 * 入力値の設定と入力チェック
	 */
	public function validate_input ($values, $rules) 
	{
		$this->clear();
		$this->input($values);
		
		foreach ($rules as $k=>$v) {
			if (is_string($v)) {
				$rules[$k] =array($v, "required");
			}
		}

		// [Deprecated]旧Validateの仕様への過渡的互換処理
		$rules_old =array();
		foreach ($rules as $k=>$v) {
			if (preg_match('!^([^\*]+)\.\*\.([^\*]+)$!', $v[0], $match)) {
				$v["options"] =$v;
				$v["target"] =$match[2];
				$v["type"] =$v[1];
				$rules_old["each"][$match[1]] =$v;
			} else {
				$v["options"] =$v;
				$v["target"] =$v[0];
				$v["type"] =$v[1];
				$rules_old["root"][] =$v;
			}
		}

		$this->validate(array(), (array)$rules_old["root"]);
		
		foreach ((array)$rules_old["each"] as $base=>$ruleset) {
			$this->validate_each($base, array(), (array)$ruleset);
		}
		
		$is_valid = ! $this->errors();
		$this->has_valid_input($is_valid);
	}

	/**
	 * 正常値が設定されているかどうか
	 */
	public function has_valid_input ($flag=null) 
	{
		if ($flag!==null) {
			$this->session("_has_valid_input",$flag);
		}
		return $this->session("_has_valid_input");
	}

	/**
	 * 検索結果ページへのリンクパラメータ組み立て
	 */
	public function merge_input ($params) 
	{
		$input =$this->input();
		$input =array_merge($input,$params);
		$this->filter_empty_value($input);
		return $input;
	}

	/**
	 * 空白要素の削除
	 */
	private function filter_empty_value ( & $values) 
	{
		foreach ($values as $k => $v) {
			if (is_array($v)) {
				$v =$this->filter_empty_value($v);
				if ( ! $v) {
					unset($values[$k]);
				}
			} else if (strlen($v)===0) {
				unset($values[$k]);
			}
		}
		return $values;
	}
}
