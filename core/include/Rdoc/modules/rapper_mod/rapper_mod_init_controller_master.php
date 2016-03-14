<?php    

    /**
    * master系ControllerのSchema/Deploy生成
    */
    function rapper_mod_init_controller_master ($r) {
    
        // schema.actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"master")),function($r,$c) {
            
            if ($r->schema("action.".$c["_id"])) { continue; }
            
            // 検索フォーム入力
            if ($c["usage"] != "form") {
                
                $r->schema("action.".$c["_id"].".view_list", array(
                    "action" =>"view_list",
                    "_id" =>$c["_id"].".view_list",
                    "label" =>"一覧表示",
                    "type" =>"master.view_list",
                ));
            }
            
            // table形式のフォーム入力
            if ($c["usage"] != "view") {
                    
                $r->schema("action.".$c["_id"].".entry_form",array(
                    "action" =>"entry_form",
                    "_id" =>$c["_id"].".entry_form",
                    "label" =>"フォーム",
                    "type" =>"master.entry_form",
                ));
                $r->schema("action.".$c["_id"].".entry_confirm",array(
                    "action" =>"entry_confirm",
                    "_id" =>$c["_id"].".entry_confirm",
                    "label" =>"確認",
                    "type" =>"master.entry_confirm",
                ));
                $r->schema("action.".$c["_id"].".entry_exec",array(
                    "action" =>"entry_exec",
                    "_id" =>$c["_id"].".entry_exec",
                    "label" =>"完了",
                    "type" =>"master.entry_exec",
                ));
            }
            
            // 削除
            if ($c["usage"] != "form" && $c["usage"] != "view") {
                
                $r->schema("action.".$c["_id"].".delete_confirm",array(
                    "action" =>"delete_confirm",
                    "_id" =>$c["_id"].".delete_confirm",
                    "label" =>"削除",
                    "type" =>"master.delete_confirm",
                ));
                $r->schema("action.".$c["_id"].".delete_exec", array(
                    "action" =>"delete_exec",
                    "_id" =>$c["_id"].".delete_exec",
                    "label" =>"削除完了",
                    "type" =>"master.delete_exec",
                ));
            }
        });
    }