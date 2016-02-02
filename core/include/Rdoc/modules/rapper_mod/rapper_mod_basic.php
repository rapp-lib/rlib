<?php

    /**
     *
     */
    function rapper_mod_basic ($r) {
        
        $r->add_mod("basic_schema_csv_loader");
        $r->add_mod("basic_load_tables");
        $r->add_mod("basic_load_pages");
        $r->add_mod("basic_model");
        $r->add_mod("basic_list_option");
        $r->add_mod("basic_account");
        $r->add_mod("basic_config_file");
        $r->add_mod("basic_controller");
        $r->add_mod("basic_deploy");
        
        $r->add_filter("config.init",array(),function($r,$config) {
            
            $work_dir =registry("Path.tmp_dir")."/rapper/workspace/".date("Ymd-His");
            
            return array_merge($config,array(
                "target_webapp_dir" =>registry("Path.webapp_dir"),
                "schema_csv_file" =>registry("Path.webapp_dir")."/config/schema.config.csv",
            ));
        });
        
        $r->add_filter("schema_index.init",array(),function($r,$schema_index) {
            
            return array_merge($schema_index,array(
                "table",
                "table.col",
                "controller",
                "controller.action",
                "config",
                "model",
                "list_option",
                "account",
            ));
        });
    }