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
		
		if (is_object($sname) && is_subclass_of($sname,"Context_Base")) {
			
			$sname ="ctx_by_ctx-".$sname->get_sname();
					
		} elseif (is_object($sname)) {
			
			$sname =str_underscore($var_name)
					."ctx_by_class-".str_underscore(get_class($sname));
		
		} elseif ($sname === null || $sname === 0) {
			
			$sname ="ctx_by_page-"
					.str_underscore($this->controller_name)
					."-".str_underscore($this->action_name);
			
		} elseif (is_numeric($sname) && $sname > 0) {
		
			$action_name =$this->action_name;
			$action_name =str_underscore($action_name);
			$action_name =explode("_",$action_name);
			$action_name =array_slice($action_name,0,-$sname);
			$action_name =implode("_",$action_name);
			
			$sname ="ctx_by_pages-"
					.str_underscore($this->controller_name)
					."-".$action_name;
		
		} else {
			
			$sname ="ctx_by_name-".$sname;
		}
		
		if ($fid_enable && $sname) {
			
			$fid_name ="f_".substr(md5($sname),0,5);
			
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