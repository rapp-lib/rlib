<?php

//-------------------------------------
// 
class Controller_Base extends SmartyExtended {

	protected $controller_name;
	protected $action_name;
	protected $vars;
	protected $contexts;
    protected $parent_controller;
	
	//-------------------------------------
	// 
	public function __construct (
			$controller_name="",
			$action_name="",
			$options=array()) {
			
		parent::__construct();
		
		$this->init($controller_name,$action_name,$options);
	}
	
	//-------------------------------------
	// 
	public function init (
			$controller_name="",
			$action_name="",
			$options=array()) {
		
		$this->controller_name =$controller_name;
		$this->action_name =$action_name;
		
		$this->vars =& $this->_tpl_vars;
		$this->vars =array();
		$this->contexts =array();
		
		// Smarty上でincにより呼び出された場合、呼び出し元が設定される
        $this->parent =$options["parent_smarty_template"];
        
		if ($this->parent_controller =$options["parent_controller"]) {
			
			$this->parent_controller->inherit_state($this);
		}
        
        // 外部からVarsを追加指定
        foreach ((array)$options["vars"] as $k => $v) {
            
            $this->vars[$k] =$v;
        }
	}
	
	//-------------------------------------
	// ほかのControllerに状態を継承する処理
	public function inherit_state ($sub_controller) {
		
		$sub_controller->vars =$this->vars;
	}
	
	//-------------------------------------
	// report出力時の値セット
	public function __report () {
		
		return array(
			"vars" =>$this->vars,
			"contexts" =>$this->contexts,
		);
	}

	//-------------------------------------
	// context
	/*
		var_name ... 変数名
		sname ... セッションID（null:ページ固有 / false:無効 / n:縮退）
		fid_enable ... フォーム機能付加
		class_name ... Contextクラス名
	*/
	public function context (
			$var_name, 
			$sname=null, 
			$fid_enable=false,
			$options=array()) {
		
        $class_name =is_string($options) ? $options : 
                ($options["class"] ? $options["class"] : "Context_App");
        $sname_scope =isset($options["scope"]) 
                ? $options["scope"] : str_underscore($this->controller_name);
        
		$context =new $class_name;
		
		$this->$var_name =$context;
		$this->contexts[$var_name] =$context;
		$this->vars[$var_name] =$context;
        
		if (is_object($sname) && is_subclass_of($sname,"Context_Base")) {
			
			$sname =$sname->get_sname();
			
		} else if (is_object($sname)) {
			
			$sname =str_underscore(get_class($sname));
		
		} else if (is_string($sname)) {
		    
            $sname =$sname_scope.".".$sname;
                  
		} else if ($sname === null || $sname === 0) {
			
			$sname =$sname_scope.".".str_underscore($this->action_name);
			
			if ($fid_enable===true) {
				
				$fid_enable =str_underscore($this->controller_name)
						.".".str_underscore($this->action_name);
			}
			
		} else if (is_numeric($sname) && $sname > 0) {
		
			$action_name =$this->action_name;
			$action_name =str_underscore($action_name);
			$action_name =explode("_",$action_name);
			$action_name =array_slice($action_name,0,-$sname);
			$action_name =implode("_",$action_name);
			
			$sname =$sname_scope
					."-".$action_name;
			
			if ($fid_enable===true) {
				
				$fid_enable =str_underscore($this->controller_name)
						.".".$action_name."*";
			}
		
		}
		
        // fid_enable設定によるURL書き換え処理の登録
		if ($fid_enable && $sname) {
			
			$fid_name ="f_".substr(md5($sname),0,5);
			
            // fid_check指定がある場合、正常なfidがURLで渡らなければエラーとなる
            if ( ! strlen($_REQUEST[$fid_name]) && $options["fid_check"]) {
            
                return false;
            }
            
			$fid =strlen($_REQUEST[$fid_name])
					? $_REQUEST[$fid_name]
					: substr(md5(mt_rand(1,999999)),0,5);
			
			$sname .="-".$fid;
			
			// [Deprecated]全てのURLの書き換えを行う処理方式
			if (registry("Context.fid_pass_by_rewrite")) {
				
				output_rewrite_var($fid_name,$fid);
				output_rewrite_var("_noindex","1");
				
			} else {
				
				add_url_rewrite_rule($fid_enable, $fid_name, $fid, array(
					"sname" =>$sname,
				));
			}
		}
		
		$context->bind_session($sname);
        
        return $context;
	}
	
	//-------------------------------------
	// 
	public function before_act () {
	}
	
	//-------------------------------------
	// 
	public function after_act () {
	}
}
