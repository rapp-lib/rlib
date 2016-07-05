<?php    

    /**
    * master系ControllerのSchema/Deploy生成
    */
    function rapper_mod_init_controller_master ($r) {
    
        // schema.actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"master")),function($r,$c) {
            
            // 検索フォーム入力
            if ($c["usage"] != "form") {
                
                $r->schema("action.".$c["_id"].".view_list", array(
                    "action" =>"view_list",
                    "_id" =>$c["_id"].".view_list",
                    "label" =>"一覧表示",
                    "type" =>"master.view_list",
                    "controller" =>$c["_id"],
                ));
                $r->schema("action.".$c["_id"].".view_detail", array(
                    "action" =>"view_detail",
                    "_id" =>$c["_id"].".view_detail",
                    "label" =>"詳細表示",
                    "type" =>"master.view_detail",
                    "controller" =>$c["_id"],
                ));
                
                // CSV出力
                if ($c["use_csv"]) {
                    
                    $r->schema("action.".$c["_id"].".view_csv", array(
                        "action" =>"view_csv",
                        "_id" =>$c["_id"].".view_csv",
                        "label" =>"CSV出力",
                        "type" =>"master.view_csv",
                        "no_html" =>1,
                        "controller" =>$c["_id"],
                    ));
                }
            }
            
            // table形式のフォーム入力
            if ($c["usage"] != "view") {
                    
                $r->schema("action.".$c["_id"].".entry_form",array(
                    "action" =>"entry_form",
                    "_id" =>$c["_id"].".entry_form",
                    "label" =>"入力",
                    "type" =>"master.entry_form",
                    "controller" =>$c["_id"],
                ));
                $r->schema("action.".$c["_id"].".entry_confirm",array(
                    "action" =>"entry_confirm",
                    "_id" =>$c["_id"].".entry_confirm",
                    "label" =>"確認",
                    "type" =>"master.entry_confirm",
                    "controller" =>$c["_id"],
                ));
                $r->schema("action.".$c["_id"].".entry_exec",array(
                    "action" =>"entry_exec",
                    "_id" =>$c["_id"].".entry_exec",
                    "label" =>"完了",
                    "type" =>"master.entry_exec",
                    "controller" =>$c["_id"],
                ));
                
                // CSV登録
                if ($c["use_csv"]) {
                    
                    $r->schema("action.".$c["_id"].".entry_csv_form",array(
                        "action" =>"entry_csv_form",
                        "_id" =>$c["_id"].".entry_csv_form",
                        "label" =>"CSV登録",
                        "type" =>"master.entry_csv_form",
                        "no_html" =>1,
                        "controller" =>$c["_id"],
                    ));
                    $r->schema("action.".$c["_id"].".entry_csv_confirm",array(
                        "action" =>"entry_csv_confirm",
                        "_id" =>$c["_id"].".entry_csv_confirm",
                        "label" =>"CSV登録 確認",
                        "type" =>"master.entry_csv_confirm",
                        "no_html" =>1,
                        "controller" =>$c["_id"],
                    ));
                    $r->schema("action.".$c["_id"].".entry_csv_exec",array(
                        "action" =>"entry_csv_exec",
                        "_id" =>$c["_id"].".entry_csv_exec",
                        "label" =>"CSV登録 完了",
                        "type" =>"master.entry_csv_exec",
                        "no_html" =>1,
                        "controller" =>$c["_id"],
                    ));
                }
            }
            
            // 削除
            if ($c["usage"] != "form" && $c["usage"] != "view") {
                
                $r->schema("action.".$c["_id"].".delete_confirm",array(
                    "action" =>"delete_confirm",
                    "_id" =>$c["_id"].".delete_confirm",
                    "label" =>"削除",
                    "type" =>"master.delete_confirm",
                    "no_html" =>1,
                    "controller" =>$c["_id"],
                ));
                $r->schema("action.".$c["_id"].".delete_exec", array(
                    "action" =>"delete_exec",
                    "_id" =>$c["_id"].".delete_exec",
                    "label" =>"削除完了",
                    "type" =>"master.delete_exec",
                    "no_html" =>1,
                    "controller" =>$c["_id"],
                ));
            }
        });
    }