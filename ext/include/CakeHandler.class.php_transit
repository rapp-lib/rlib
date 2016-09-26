<?php

//-------------------------------------
// CakePHPのライブラリの機能を利用する
class CakeHandler {

	const PROCEED_NONE =0;
	const PROCEED_DISPATCH =1;
	const PROCEED_INIT =2;
	const PROCEED_BEFORE =4;
	const PROCEED_ACTION =8;
	const PROCEED_RENDER =16;
	const PROCEED_ALL =1023;

	//-------------------------------------
	// 任意の画面の処理の実行
	public function __construct () {
		
		if ( ! defined("CAKE_PHP") || constant("CAKE_PHP") == "minimam") {
			
			report_warning("Set full-CakePHP-lib dir to load_cake().");
		}
	}
	
	//-------------------------------------
	// 任意の画面の処理の実行
	public function dispatch ($url, $params=array(), $proceed=self::PROCEED_ACTION) {
		
		// Dispatcher設定
		// Controller取得に必要になるパラメータ作成
		$d =new Dispatcher;
		$d->baseUrl();
		$d->params =$d->parseParams($url,$params);
		
		// Router設定
		// URLの取得時に必要なパラメータを初期化
		// View > HtmlHelper::url > Router::url
		Router::setRequestInfo(array(
			$d->params, 
			array('base' =>$d->base, 'here' =>$d->here, 'webroot' =>$d->webroot),
		));
		
		// Controller取得
		$c =$d->__getController();
		
		if ( ! $c) {
			
			report_warning("Cannot dispatch CakeController.",array(
				"url" =>$url, 
				"params" =>$params,
			));
			
			return null;
		}
		
		if ($proceed >= self::PROCEED_INIT) {
		
			// Controller内のComponentやModelのインスタンス生成
			$c->constructClasses();
			$c->startupProcess();
		}
		
		if ($proceed >= self::PROCEED_BEFORE) {
			
			// Controller内でユーザ定義されている初期化処理の実行
			$c->beforeFilter();
		}
		
		if ($proceed >= self::PROCEED_ACTION) {
			
			// Controller内部の処理で必要になるパラメータを設定
			$c->base = $d->base;
			$c->here = $d->here;
			$c->webroot = $d->webroot;
			$c->plugin = isset($d->params['plugin']) ? $d->params['plugin'] : null;
			$c->params =& $d->params;
			$c->action =& $d->params['action'];
			$c->data =& $d->params['data'];
			$c->autoRender = false;
			$c->passedArgs = array_merge($d->params['pass'], $d->params['named']);
			
			// Controller内でユーザ定義されているActionの呼び出し
			call_user_func_array(array($c,$d->params['action']),$d->params['pass']);
		}
		
		if ($proceed >= self::PROCEED_RENDER) {
			
			// Viewを実行してHTMLを取得
			$render =$c->render($d->params['action']);
		}
		
		return array(
			"dispatcher" =>$d,
			"controller" =>$c,
			"vars" =>$c->viewVars,
			"render" =>$render,
		);
	}
	
	//-------------------------------------
	// 任意のControllerの取得
	// * Dispatcherを介さず、最低限のインスタンス生成のみ行う
	public function get_controller ($name=null,$proceed=self::PROCEED_BEFORE) {
		
		static $cache =array();
		
		if ( ! $name) {
			
			$name ="App";
		}
		
		if (isset($cache[$proceed][$name])) {
			
			return $cache[$proceed][$name];
		}
		
		App::import('Controller',$name);
		$class_name =$name."Controller";
		$c =new $class_name;
		
		if ($proceed >= self::PROCEED_INIT) {
		
			$c->constructClasses();
			$c->startupProcess();
		}
		
		if ($proceed >= self::PROCEED_BEFORE) {
		
			$c->beforeFilter();
		}
		
		$cache[$proceed][$name] =$c;
		
		return $c;
	}
	
	//-------------------------------------
	// 任意のComponentの取得
	public function get_component ($name, $c=null) {
		
		if ( ! is_object($c)) {
		
			$c =$this->get_controller($c);
		}
		
		foreach (explode(".",$name) as $name_part) {
		
			$c =$c->$name_part;
		}
		
		return $c;
	}	
	
	//-------------------------------------
	// 任意のModelの取得
	public function get_model ($name, $c=null) {
		
		if ( ! is_object($c)) {
		
			$c =$this->get_controller($c);
		}
		
		return $c->loadModel($name) ? $c->$name : null;
	}
}