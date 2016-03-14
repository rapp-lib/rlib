<?php 
	
	/**
     * schema.config.csv→生成/展開
     */ 
	function rdoc_entry_rapper ($options=array()) {
            
        // Schemaの初期化
		$r =new Rapper;
        $r->require_mod("app_root");
        $r->apply_filters("init",$options);
        $r->apply_filters("proc",$options);
	}
