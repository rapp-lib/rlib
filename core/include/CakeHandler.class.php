<?php

//-------------------------------------
// CakePHPのライブラリの機能を利用する
class CakeHandler {

	//-------------------------------------
	// 任意の画面の処理の実行
	public function __construct () {
		
		load_cake();
		
		if (constant("CAKE_PHP") == "minimam") {
			
			report_warning("Set full-CakePHP-lib dir to registry Path.cake_dir.");
		}
	}
	
	//-------------------------------------
	// 任意の画面の処理の実行
	public function dispatch ($url, $params=array(), $render=false) {
		
		$d =new Dispatcher;
		$d->params =$d->parseParams($url,$params);
		$c =$d->__getController();
		$c->constructClasses();
		$c->startupProcess();
		$c->beforeFilter();
		$c->base = $d->base;
		$c->here = $d->here;
		$c->webroot = $d->webroot;
		$c->plugin = isset($d->params['plugin']) ? $d->params['plugin'] : null;
		$c->params =& $d->params;
		$c->action =& $d->params['action'];
		$c->data =& $d->params['data'];
		$c->autoRender = false;
		$c->passedArgs = array_merge($d->params['pass'], $d->params['named']);
		call_user_func_array(array($c,$d->params['action']),$d->params['pass']);
		
		return array(
			"dispatcher" =>$d,
			"controller" =>$c,
			"vars" =>$c->viewVars,
			"render" =>$render ? $c->render($d->params['action']) : null,
		);
	}
	
	//-------------------------------------
	// 任意のControllerの取得
	// * Dispatcherを介さず、最低限のインスタンス生成のみ行う
	public function get_controller ($name="App") {
		
		static $cache =array();
		
		if (isset($cache[$name])) {
			
			return $cache[$name];
		}
		
		App::import('Controller',$name);
		$class_name =$name."Controller";
		$c =new $class_name;
		$c->constructClasses();
		$c->startupProcess();
		$c->beforeFilter();
		
		$cache[$name] =$c;
		return $cache[$name];
	}
	
	//-------------------------------------
	// 任意のComponentの取得
	public function get_component ($name=null, $c=null) {
		
		if ( ! $c) {
		
			$c =$this->get_controller();
		}
		
		$components =array();
		
		foreach ((array)$c->components as $component_name) {
		
			$components[$component_name] =$c->$component_name;
		}
		
		return $name
				? $components[$name]
				: $components;
	}	
	
	//-------------------------------------
	// 任意のModelの取得
	public function get_model ($name, $c=null) {
		
		if ( ! $c) {
		
			$c =$this->get_controller();
		}
		
		return $c->loadModel($name) ? $c->$name : null;
	}
}