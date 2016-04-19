<?php
	
	//-------------------------------------
	// Cakeモジュールの読み込み
	function cake_lib () {
        
        static $cake_lib;
        
        if ( ! $cake_lib) {
            
            if (registry("Config.cake_lib") == "rlib_cake2") {
                
                $cake_lib =new Cake2Loader;
                
            } else {
                
                $cake_lib =new CakeLoader;
            }
        }
        
        return $cake_lib;
    }
	