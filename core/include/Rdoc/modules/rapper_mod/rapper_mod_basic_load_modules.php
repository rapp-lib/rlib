<?php

    /**
     *
     */
    function rapper_mod_basic_load_modules ($r) {
        
        /**
         * table内のcolを用途に応じて取得
         */
        $r->register_function("get_fields", function ($t_name, $ref_tmpl, $ref_page=null) {
            
            $tc_names =array();
            
            // page側での限定があれば優先、なければtmpl中での指定に従う
            if ($ref_page) {
                
                if ( ! $this->schema["table"][$t_name]["refs"][$ref_page]) {
                    
                    report_error("Schema.table ref参照解決エラー",array(
                        "ref_page" =>$ref_page,
                        "table" =>$t_name,
                        "refs" =>$this->schema["table"][$t_name]["refs"],
                    ));
                }
                 
                $tc_names =$this->schema["table"][$t_name]["refs"][$ref_page];
                
            } else {
                
                $tc_names =(array)$this->schema["table"][$t_name]["refs"][$ref_tmpl];
            }
            
            // refsに設定されたtc_nameに対応するfieldを返す
            $fields =array();
            
            foreach ($tc_names as $tc_name) {
                
                $fields[] =$this->schema["table"][$t_name]["col"][$tc_name];
            }
            
            return $fields;
        });
    }