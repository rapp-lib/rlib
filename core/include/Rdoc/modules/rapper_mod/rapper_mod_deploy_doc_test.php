<?php    

    /**
    * テスト仕様書CSVの生成
    */
    function rapper_mod_deploy_doc_test ($r) {
        
        $r->require_mod("proc_doc_csv");
        
        $r->add_filter("init.deploy",array(),function($r) {
            
            $data_callback =function ($r) {
                
                $rows =array(
                    "controller"=>"機能群",
                    "action"=>"画面",
                    "action_id"=>"画面ID",
                    "item"=>"要素",
                    "type"=>"入力形式",
                    "required"=>"必須",
                    "length"=>"文字数",
                    "rules"=>"制約",
                );
                $data =array();
                
                foreach ((array)$r->schema("controller") as $c) {
                    
                    $data[] =array(
                        "wrapper"=>$r->label("wrapper",$c["wrapper"]),
                        "controller"=>$r->label("controller",$c["_id"]),
                    );
                    
                    foreach ((array)$r->schema("action.".$c["_id"]) as $a) {
                        
                        $data[] =array(
                            "action"=>$r->label("action",$a["_id"]), 
                            "action_id"=>$a["_id"],
                        );
                            
                        // ログインフォーム
                        if ($a["type"] == "login.entry_form") {
                            
                            $data[] =array(
                                "item"=>"ログインID",
                                "type"=>$r->label("input_type","text"),
                                "required"=>"○",
                            );
                            $data[] =array(
                                "item"=>"パスワード",
                                "type"=>$r->label("input_type","text"),
                                "required"=>"○",
                            );
                        }
                        
                        // 検索フォーム入力
                        if ($a["type"] == "master.view_list") {
                            
                            $data[] =array(
                                "item"=>"(検索条件)",
                            );
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
                                    "item"=>$r->label("col",$col["_id"]),
                                    "type"=>$r->label("input_type",$col["type"]),
                                    "rules"=>implode(",", (array)$r->label("rule",$col["rules"])),
                                    "required"=>($rel == "required") ? "○" : "",
                                );
                            }
                        }
                    }
                }
                
                return array(
                    "rows"=>$rows,
                    "data"=>$data,
                );
            };
            
            $r->deploy("doc.input_desc",array(
                "dest_file" =>"docs/input_desc.csv",
                "data_type" =>"doc_csv",
                "data_callback" =>$data_callback,
            ));
        });
    }