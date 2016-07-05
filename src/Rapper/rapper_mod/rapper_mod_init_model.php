<?php 

    /**
    * Modelに関するschema/deployの生成
    */
    function rapper_mod_init_model ($r) {
        
        // init.schema [table]
        // ->[model]
        $r->add_filter("init.schema.table",array(),function($r, $t) {
            
            if ($t["nomodel"]) { return; }
            
            $_id =$t["_id"];
            $r->schema("model.".$_id,array(
                "_id" =>$_id,
                "table" =>$t["_id"],
            ));
        });
        
        // init.deploy [model]
        // ->/app/model/XxxModel.class.php
        $r->add_filter("init.deploy.model",array(),function($r, $model) {
            
            $r->deploy("model.".$model["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"model/XxxModel.class.php",
                "dest_file" =>"app/model/".$model["_id"]."Model.class.php",
            ));
        });
    }