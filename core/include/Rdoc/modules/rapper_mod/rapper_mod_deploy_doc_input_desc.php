<?php

    /**
    * 入力仕様書CSVの生成
    */
    function rapper_mod_deploy_doc_input_desc ($r) {
        
        $r->require_mod("proc_doc_csv");
        
        $r->add_filter("init.deploy",array(),function($r) {
            
            $data_callback =function ($r) {
                
                $rows =array(
                    "wrapper"=>"大分類",
                    "controller"=>"小分類",
                    "action"=>"画面",
                    "type"=>"テスト種別",
                    "item"=>"対象要素",
        			"cond" =>"前提条件",
        			"trigger" =>"操作/トリガー",
        			"expect" =>"期待値",
        			"etc" =>"備考",
        			"test_date" =>"テスト実施日時",
        			"test_result" =>"テスト結果",
        			"test_etc" =>"テスト結果備考",
                );
                $data =array();
                
                foreach ($r->schema("controller") as $c) {
                    
                    $data[] =array("controller"=>$r->label("controller",$c["_id"]));
                    
                    if ($c["auth"]) {
                        
                        $data[] =array(
                            "type"=>"認証",
                            "item"=>"",
                            "cond" =>$r->label("account",$c["auth"])."でログインしていない状態",
                            "trigger" =>"各画面へのアクセス",
                            "expect" =>"ログイン画面へ転送される",
                        );
                    }
                    
                    foreach ((array)$r->schema("action.".$c["_id"]) as $a) {
                        
                        $data[] =array(
                            "action"=>$r->label("action",$a["_id"]), 
                            "action_id"=>$a["_id"],
                        );
                        
                        // ログインフォーム
                        if ($a["type"] == "login.entry_form") {
                            
                            $data[] =array(
                                "type"=>"入力",
                                "item"=>"ログインID",
                                "cond" =>"",
                                "trigger" =>"",
                                "expect" =>$r->label("input_type","text")."として入力可能",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"ログインID",
                                "cond" =>"入力しない",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                            $data[] =array(
                                "type"=>"入力",
                                "item"=>"パスワード",
                                "cond" =>"",
                                "trigger" =>"",
                                "expect" =>$r->label("input_type","text")."として入力可能",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"パスワード",
                                "cond" =>"入力しない",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                            $data[] =array(
                                "type"=>"入力エラー",
                                "item"=>"パスワード",
                                "cond" =>"ログインIDと誤った組み合わせで入力",
                                "trigger" =>"フォーム送信",
                                "expect" =>"エラーとなる",
                            );
                        }
                        
                        // 検索フォーム入力
                        if ($a["type"] == "master.view_list") {
                            
                        }
                        
                        // table形式のフォーム入力
                        if ($a["type"] == "master.entry_form") {
                            
                            $table =$a["table"] ? $a["table"] : ($c["table"] ? $c["table"] : "");
                            $rel_id =$a["rel"] ? $a["rel"] : ($c["rel"] ? $c["rel"] : "default");
                            
                            // 関連するtableがなければ対象外
                            if ( ! $table) { continue; }
                            
                            foreach ((array)$r->schema("col.".$table) as $col_id => $col) {
                                
                                // 関係する入力項目（rel=input|required）以外を除外
                                $rel =$col["rels"][$rel_id];
                                if ($rel != "input" && $rel != "required") { continue; }
                                
                                $data[] =array(
                                    "type"=>"入力",
                                    "item"=>$r->label("col",$col["_id"]),
                                    "cond" =>"",
                                    "trigger" =>"",
                                    "expect" =>$r->label("input_type",$col["type"])."として入力可能",
                                );
                                
                                if ($rel == "required") {
                                    
                                    $data[] =array(
                                        "type"=>"入力エラー",
                                        "item"=>$r->label("col",$col["_id"]),
                                        "cond" =>"入力しない",
                                        "trigger" =>"フォーム送信",
                                        "expect" =>"エラーとなる",
                                    );
                                }
                                
                                foreach ((array)$col["rules"] as $rule) {
                                    
                                    $data[] =array(
                                        "type"=>"入力エラー",
                                        "item"=>$r->label("col",$col["_id"]),
                                        "cond" =>$r->label("rule",$rule)."に対して不正な形式で入力",
                                        "trigger" =>"フォーム送信",
                                        "expect" =>"エラーとなる",
                                    );
                                }
                            }
                            
                            $data[] =array(
                                "type"=>"遷移",
                                "item"=>"",
                                "cond" =>"入力エラーがない状態",
                                "trigger" =>"フォーム送信",
                                "expect" =>"確認画面に遷移",
                            );
                        }
                    }
                }
                
                return array(
                    "rows"=>$rows,
                    "data"=>$data,
                );
            };
            
            $r->deploy("doc.test",array(
                "dest_file" =>"docs/test.csv",
                "data_type" =>"doc_csv",
                "data_callback" =>$data_callback,
            ));
        });
    }
