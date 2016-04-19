<?php
	
	register_shutdown_webapp_function("dbi_rollback_all");
	
	//-------------------------------------
	// DBIインスタンスのファクトリ
	function dbi ($name=null) {
		
		$instance =& ref_globals("loaded_dbi");
	
		if ( ! $name) {
			
			$name ="default";
		}
		
		if ( ! $instance[$name]) {
		
			if (($connect_info =registry("DBI.connection.".$name))
					|| ($connect_info =registry("DBI.preconnect.".$name))) {
				
				$class =$connect_info["class"]
						? $connect_info["class"]
						: "DBI_App";
				$instance[$name] =new $class($name);
				$instance[$name]->connect($connect_info);
			
			} else {
			
				$instance[$name] =new $class($name);
			}
		}
		
		return $instance[$name];
	}
	
	//-------------------------------------
	// 全てのトランザクションのRollback
	function dbi_rollback_all () {
		
		$instance =& ref_globals("loaded_dbi");
		
		foreach ((array)$instance as $dbi) {
			
			$result =$dbi->rollback();
			
			if ($result) {
				
				report_warning("Rollback unclosed Trunsaction");
			}
		}
	}
	
	//-------------------------------------
	// Modelインスタンスのファクトリ
	function model ($name=null, $accessor=null) {
		
		$instance =& ref_globals("loaded_model");
		
		$class_name = ! $name 
				? "Model_App" : (class_exists($name."Model_For".str_camelize($accessor))
		 		? $name."Model_For".str_camelize($accessor) : $name."Model");
		
		$id =($name ? $name : "_").($accessor ? ".".$accessor : "");
		
		if ( ! $instance[$id]) {
			
			$instance[$id] =new $class_name;
			$instance[$id]->bind_accessor($accessor);
		}
		
		return $instance[$id];
	}