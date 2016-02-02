<?php 
	
	/**
     * schema.config.php→生成/展開
     */ 
	function rdoc_entry_rapper_deploy ($options=array()) {
        
		$r =new Rapper($option["root_mod"],(array)$option["config"]);
        
        if ($option["deploy_all"]) {
            
            foreach ($r->get_deployee() as $deployee) {
                
                $r->deploy($deployee);
            }
        }
	}
