<?php

    /**
    * Accountに関するschema/deployの生成
    */
    function rapper_mod_init_account ($r) {
        
        // init.schema [controller]
        // ->[account]
        $r->add_filter("init.schema.controller",array("cond"=>array("auth")),function($r, $c) {
            
            $_id =$c["auth"];
            $r->schema("account.".$_id,array(
                "_id" =>$_id,
            ));
        });
        
        // init.deploy [account.*]
        // ->app/context/XxxContext.class.php
        $r->add_filter("init.deploy.account",array(),function($r, $account) {
            
            $r->deploy("auth_context.".$account["_id"],array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"context/XxxAuthContext.class.php",
                "dest_file" =>"app/context/".str_camelize($account["_id"])."AuthContext.class.php",
            ));
        });
    }