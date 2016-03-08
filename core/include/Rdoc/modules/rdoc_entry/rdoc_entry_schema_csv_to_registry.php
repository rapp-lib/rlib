<?php 
	
	//-------------------------------------
	// schema.config.csv→config生成
	function rdoc_entry_schema_csv_to_registry ($options=array()) {
		
		$src_file =registry("Path.webapp_dir")."/config/schema.config.csv";
        $dest_file =registry("Path.webapp_dir")."/config/_schema.config.php";
		
        // CSV読み込み
        $loader =new SchemaCsvLoader;
		$schema =$loader->load_schema_csv($src_file);
        
		report("Schema csv loaded.",array("schema" =>$schema));
        
		// スクリプト生成
		$g =new ScriptGenerator;
		$g->node("root",array("p",array(
			array("c","Schama created from csv-file."),
			array("v",array("c","registry",array(
				array("a",$g->make_array_node($schema)),
			)))
		)));
		$script =$g->get_script();
        
        // PHP配置
        $s =new RdocSession;
        $s->deploy_src($dest_file, $script);
	}
