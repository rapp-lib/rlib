<?php 

class ModuleProvider {
	
	protected $routing =array();
	
	private $root_provider =null;
	private $registry =array();
	private $loaded_module =array();
	
	public function call ($path, $params=array()) {
		
		// root参照
		if ($this->root_provider) {
			return $this->root_provider->call($path,$params);
		}
		
		// Moduleを検索して呼び出す
		$module =$this->search_module($path);
		
		if ( ! is_callable($module)) {
			
			report_error("Module-function is invalid",array(
				"provider" =>get_class($this),
				"path" =>$path,
				"module" =>$module,
			));
		}
		
		try {
			
			$result =call_user_func_array($module,array($params));
		
		} catch (Exception $e) {
			
			$result =$this->exception_handler($e);
		}
		
		return $result;
	}
	
	public function registry ($name=null, $value=null) {
		
		// root参照
		if ($this->root_provider) {
			return $this->root_provider->registry($name, $value);
		}
		return array_registry($this->registry,$name,$value);
	}
	
	protected function search_module ($path) {
		
		// 読み込み済みのModuleを探す
		$module =$this->loaded_module[$path];
		
		// $this内のModule定義を探す
		if ( ! is_callable($module)) {
			
			$module =$this->path_to_module_callback($path);
		}
		
		// Path上位に向かってProviderを検索する
		if ( ! is_callable($module)) {
			
			$split_path =explode('.',$path);
			
			while ($split_path) {
				
				$tmp_path =implode('.',$split_path);
				
				if ($provider_class =$this->path_to_provider_class($tmp_path)) {
					
					$provider =new $provider_class;
					$provider->bind_root_provider($this);
					$module =$provider->search_module($path);
					break;
				}
				
				array_pop($split_path);
			}
		}
		
		$this->loaded_module[$path] =$module;
		
		return $module;
	}
	
	private function bind_root_provider ($root_provider) {
		
		$this->root_provider =$root_provider;
	}
	
	// for overwirite
	protected function path_to_provider_class ($path) {
		
		$provider_class =$this->routing[$path];
		
		if (preg_match('!^[a-z]!',$provider_class)) {
			
			$provider_class =str_replace(' ','',ucwords(str_replace('_', ' ', $provider_class)));
			$provider_class =str_replace('.','_',$provider_class);
		}
		
		return $provider_class;
	}
	
	// for overwirite
	protected function path_to_module_callback ($path) {
		
		$method_name =str_replace('.','_',$path);
		return array($this,$method_name);
	}
	
	// for overwirite
	protected function exception_handler ($e) {
		
		throw $e;
		return null;
	}
}
	