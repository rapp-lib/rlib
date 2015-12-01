<?php

class EcbProvider_Base extends ModuleProvider {
	
	protected function path_to_module_callback ($path) {
		
		$method_name ="ecb_".str_replace('.','_',$path);
		return array($this,$method_name);
	}
}