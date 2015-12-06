<?php 
/*
http://blog.fagai.net/2015/05/06/laravel-ioc-service-container-1/
※Laravel4 IoCコンテナのServiceProviderの考え方を参考に設計。
※Modulesの統合と、Controller/Modelの分解の双方を解決するソリューションとして導入。
※一旦、ECBProviderに必要な機能のみ実装。今後、Call/Strage参照の他にMake/Singleton参照、対応するListenerも導入予定。
★registryの階層化を行う
	registry（Strage）クラスをregistryに登録すると、その階層以下の挙動が変わる
	Templateに渡す領域や、Contextを経由してSessionに書き込む処理などが実装可能
	Strage->array_regostry実装をStrageクラスで拡張する
ModuleProviderはAppにcall:Callback、make:Objectを提供する
	$x =new XProvider_App;
	$x->add_provider("a"=>"ecb_provider.basic");
	$x->call("a.hello","test");
	$x->make("a")->hello();
	
【定義例】
	// 参照体系のルールを変更したい場合、必要に応じてoverrideを行う
	class XProvider_App extends ModuleProvider {
	}
	// provider_class別でroutingを記述、callbackとなるmethodの定義を行う
	class XProvider_A extends XProvider_App {
		public function call_a_hello ($msg) {}
		public function make_a () {
			return new XProvider_A;
		}
		public function hello () {}
	}
	
【利用例】
	// $xprovider_aがroot_providerとしてModule参照体系が生成される
	$xprovider_a =new XProvider_A;
	$result =$xprovider_a->call("b.xxx",array("test"=>1));

【基本設計】
	旧module.php系（obj(),load_module()等）を統合した上位実装となる
	「root_provider以外のインスタンスは使い捨てであり状態を持たない」
	Module同士の関係性を定義する「Module参照体系」を生成するためのインタフェイスを持つ
	
【用語の説明】
	Module参照体系
		ModuleProviderのインスタンスが生成する閉じた変数、関数の参照系
		root_providerの生成後、Callback参照はcall()、Strage参照はregistry()から行う
	provider_class
		ModuleProviderを継承したClass
		実装されたprovider_class同士では継承を行わない
	provider
		provider_classのインスタンス
		ModuleProvider外から参照できるのはroot_providerのみ
	root_provider
		call()とregistry()の基点となるprovider
		Module参照体系外から参照可能であり、体系内唯一状態を保持するオブジェクト
	class_path
		クラスに対する参照
		例えば"SampleClass_Base_Origin"に対して"sample_class.base.origin"
		
	module_path
		root_providerの体系を元に呼び出したいモジュールの参照
		root_providerを基点としてmodule_callbackを探して呼び出す
		$provider->call($module_path)のように使用する
	module_callback
		生成したModule参照体系
	add_provider
		各provider内にmodule_callbackがなかった場合に参照を行うprovider_class_pathを登録する
		参照された各providerを基準に再度routingの参照を行う
		call()後の参照はsearch_module()により再帰的に探索する
		
	loaded_module
		各provider上で参照したことのあるmodule_pathに対するcallbackのキャッシュ
*/
class ModuleProvider {
	
	protected $singletons =array();
	
	private $providers =array();
	private $root_provider =null;
	private $loaded_module =array();
	
	//-------------------------------------
	// 初期化
	public function __construct ($root_provider=null) {
		
		$this->root_provider =$root_provider
				? $root_provider
				: $this;
        $this->init($this->root_provider);
	}
	
	//-------------------------------------
	// 初期化
	protected function init ($root_provider=null) {
	}
	
	//-------------------------------------
	// module_pathに対する探索先のprovider_class_pathを登録する
	public function add_provider ($module_path, $provider_class_path=null) {
		
		// module_pathに複数指定のある場合
		if (is_array($module_path)) {
			
			foreach ($module_path as $k => $v) {
				
				$this->add_provider($k,$v);
			}
			
			return;
		}
		
		// provider_class_pathに複数指定のある場合
		if (is_array($provider_class_path)) {
			
			foreach ($provider_class_path as $v) {
				
				$this->add_provider($module_path,$v);
			}
			
			return;
		}
		
		$provider_class =$this->_path_to_class($provider_class_path);
		$provider =class_exists($provider_class)
                ? new $provider_class($this)
                : null;
		
		if ( ! is_a($provider,"ModuleProvider")) {
			
			report_error("Provider-class is invalid",array(
				"resolver_provider_class" =>get_class($this),
				"module_path" =>$module_path,
				"provider_class_path" =>$provider_class_path,
				"provider_class" =>$provider_class,
			));
		}
		
		$this->providers[$module_path] =(array)$this->providers[$module_path];
		array_unshift($this->providers[$module_path],$provider);
	}
	
	//-------------------------------------
	// root_providerを基準にmodule_pathからcallbackを検索して呼び出す
	public function call () {
		
		$args =func_get_args();
		$module_path =array_shift($args);
		array_unshift($args,$this->root_provider);
        
		$module_callback =$this->search_module($module_path,"call");
		$result =$this->call_module_callback($module_path, $module_type, $module_callback, $args);
		
		return $result;
	}
	
	//-------------------------------------
	// root_providerを基準にmodule_pathからインスタンス生成してシングルトン化して返す
	public function obj () {
		
		$args =func_get_args();
		$module_path =array_shift($args);
		array_unshift($args,$this->root_provider);
        
		if ( ! $this->root_provider->singletons[$module_path]) {
			
			$module_callback =$this->search_module($module_path,"make");
			$result =$this->call_module_callback($module_path, $module_type, $module_callback, $args);
			
			$this->root_provider->singletons[$module_path] =$result;
		}
		
		return $this->root_provider->singletons[$module_path];
	}
	
	//-------------------------------------
	// root_providerを基準にmodule_pathからcallbackを検索する
	protected function search_module ($module_path, $module_type) {
		
		return $this->root_provider->exec_search_module($module_path, $module_type);
	}
	
	//-------------------------------------
	// このProviderを基準にmodule_pathからcallbackを検索する
	protected function exec_search_module ($module_path, $module_type) {
		
		$module_callback =null;
		
		// 読み込み済みのModuleを探す
		if ( ! $module_callback) {
			
			$module_callback =$this->loaded_module[$module_path];
		}
			
		// $this内のModule定義を探す
		if ( ! $module_callback) {
			
			$module_callback =$this->module_path_to_callback($module_path,$module_type);
		}
			
		// 参照先のProviderを探す
		if ( ! $module_callback) {
            
            $providers =$this->search_provider($module_path);
            
            foreach ((array)$providers as $provider) {
                
			    $module_callback =$provider->exec_search_module($module_path,$module_type);
                
                if ($module_callback) {
                    
                    break;
                }
            }
		}
		
		$this->loaded_module[$module_path] =$module_callback;
		
		return $module_callback;
	}
	
	//-------------------------------------
	// OverRide module_pathからproviderを解決するルール定義
	protected function search_provider ($module_path) {
		
		$split_path =explode('.',$module_path);
		
		do {
			
			$tmp_path =implode('.',$split_path);
			
			if ($provider =$this->providers[$tmp_path]) {
				
				return $provider;
			}
			
		} while (array_pop($split_path));
		
		return null;
	}
	
	//-------------------------------------
	// OverRide module_callbackの呼び出し処理
	protected function call_module_callback ($module_path, $module_type, $module_callback, $args) {
		
		if ( ! is_callable($module_callback)) {
			
			report_error("Module-callback is invalid",array(
				"resolver_provider_class" =>get_class($this),
				"module_path" =>$module_path,
				"module_type" =>$module_type,
				"module_callback" =>$module_callback,
			));
		}
		
		$result =call_user_func_array($module_callback,$args);
		
		return $result;
	}
	
	//-------------------------------------
	// OverRide module_pathからcallbackを解決するルール定義
	protected function module_path_to_callback ($path, $type) {
		
		$method_name =$type."_".str_replace('.','_',$path);
		$callback =array($this,$method_name);
		
		return is_callable($callback) ? $callback : null;
	}
	
	//-------------------------------------
	// path_to_class
	private function _path_to_class ($path) {
		
		$class =str_replace('.',' . ',$path);
		$class =str_replace(' ','',ucwords(str_replace('_', ' ', $class)));
		$class =str_replace('.','_',$class);
		
		return $class;
	}
	
	//-------------------------------------
	// class_to_path
	private function _class_to_path ($class) {
		
		$path =str_replace('_','.',$class);
		$path =strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $path));
		
		return $path;
	}
}

class Registry {
	
	private $_registry_hive =array();
	
	//-------------------------------------
	// このProviderのregistry_baseを基準に、root_provider上のhiveを参照する
	public function _registry ($name=null, $value=null) {
		
		// root参照
		return $this->root_provider
				? $this->root_provider->exec_registry($name, $value)
				: $this->exec_registry($name, $value);
	}
	
	//-------------------------------------
	// root_provider上で実行されるregistryの実装
	protected function _exec_registry ($name=null, $value=null) {

		return array_registry($this->registry_hive,$name,$value);
	}
}