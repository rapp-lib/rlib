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
			$parent_controller=null) {
			
		parent::__construct();
		
		$this->init($controller_name,$action_name,$parent_controller);
	}
	
	//-------------------------------------
	// 
	public function init (
			$controller_name="",
			$action_name="",
			$parent_controller=null) {
		
		$this->controller_name =$controller_name;
		$this->action_name =$action_name;
		$this->parent_controller =$parent_controller;
		$this->vars =& $this->_tpl_vars;
		$this->vars =array();
		$this->contexts =array();
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
			$class_name="Context_App") {
		
		$context =new $class_name;
		
		$this->$var_name =$context;
		$this->contexts[$var_name] =$context;
		$this->vars[$var_name] =$context;
		
		$page_code =str_underscore($var_name)
				."-".str_underscore($this->controller_name)
				."-".str_underscore($this->action_name);
		
		if ($sname === null) {
			
			$sname =$page_code;
			
		} elseif (is_numeric($sname)) {
			
			if ($sname == 0) {
			
				$sname =$page_code;
				
			} elseif ($sname > 0) {
			
				$sname =implode("_",array_slice(explode("_",$page_code),0,-$sname));
			
			} elseif ($sname < 0) {
			
				$sname =str_underscore($var_name)
						."-".str_underscore($this->controller_name);
			}
		}

		if ($fid_enable && $sname) {
			
			$fid_name ="_f_".substr(md5($sname),0,5);
			
			$fid =strlen($_REQUEST[$fid_name])
					? $_REQUEST[$fid_name]
					: substr(md5(mt_rand(1,999999)),0,5);
			
			$sname .="-".$fid;
			
			output_rewrite_var($fid_name,$fid);
		}
		
		$context->bind_session($sname);
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