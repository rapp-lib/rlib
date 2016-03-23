<?php
    
    /**
    * login系ControllerのSchema/Deploy生成
    */
    function rapper_mod_init_controller_login ($r) {
    
        // schema.actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"login")),function($r,$c) {
            
            if ($r->schema("action.".$c["_id"])) { continue; }
            
            $r->schema("action.".$c["_id"].".entry_form", array(
                "action" =>"entry_form",
                "_id" =>$c["_id"].".entry_form",
                "label" =>"ログイン",
                "type" =>"login.entry_form",
            ));
            $r->schema("action.".$c["_id"].".logout",array(
                "action" =>"logout",
                "_id" =>$c["_id"].".logout",
                "label" =>"ログアウト",
                "type" =>"login.logout",
            ));
        });
    }
