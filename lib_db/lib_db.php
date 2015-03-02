<?php
	
	load_cake();
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
	function model ($name=null) {
		
		$instance =& ref_globals("loaded_model");
		
		$name =$name
				? $name."Model"
				: "Model_App";
		
		if ( ! $instance[$name]) {
			
			$instance[$name] =new $name;
		}
		
		return $instance[$name];
	}