<?php


//-------------------------------------
// 
class Controller_Base extends SmartyExtended {

	protected $controller_name;
	protected $action_name;
	protected $vars;
	protected $contexts;
	
	//-------------------------------------
	// 
	public function __construct (
			$controller_name,
			$action_name) {
			
		parent::__construct();
		
		$this->controller_name =$controller_name;
		$this->action_name =$action_name;
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
		$this->contexts[$var_name] =$context;
		$this->$var_name =$context;
		
		$page_code =str_underscore($var_name)
				.":".str_underscore($this->controller_name)
				.":".str_underscore($this->action_name);
		
		if ($sname === null) {
			
			$sname =$page_code;
			
		} elseif (is_numeric($sname)) {
			
			if ($sname == 0) {
			
				$sname =$page_code;
				
			} elseif ($sname > 0) {
			
				$sname =implode("_",array_slice(explode("_",$page_code),0,-$sname));
			
			} elseif ($sname < 0) {
			
				$sname =implode("_",array_slice(explode("_",$page_code),$sname));
			}
		}

		if ($fid_enable && $sname) {
			
			$fid_name ="__form_".$sname;
			
			$fid =strlen($_REQUEST[$fid_name])
					? $_REQUEST[$fid_name]
					: sprintf("%09d",mt_rand(1,mt_getrandmax()));
			
			$sname .=":".$fid;
			
			output_rewrite_var($fid_name,$fid);
		}
		
		$context->bind_session($sname);
	}
	
	//-------------------------------------
	// 
	public function before_act () {
	
		// ファイルアップロード
		obj("UserFileManager")->fetch_file_upload_request();
	}
	
	//-------------------------------------
	// 
	public function after_act () {
	
		$this->_tpl_vars =$this->vars;
		$this->_tpl_vars["_REQUEST"] =$_REQUEST;
		$this->_tpl_vars["_REGISTRY"] =registry(null);
		
		foreach ($this->contexts as $var_name => $context) {
			
			$this->_tpl_vars[$var_name] =$context;
		}
		
		array_extract($this->_tpl_vars);
	}
}