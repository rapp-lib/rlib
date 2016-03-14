<?php

    /**
     *
     */
    function rapper_mod_basic_load_tables ($r) {
        
        // table初期化設定
        $r->add_filter("table_init",array(), function ($r, $t) {
            if ( ! $t["def"]["table"]) { $t["def"]["table"] =$t["name"]; }
            return $t;
		});
        
        // col初期化設定
        $r->add_filter("col_init",array("type"=>"date"), function ($r, $tc) {
            $tc['modifier'][] ='|date:"Y/m/d"';
            $tc['input_option']["range"]=date("Y").'~+5';
            $tc['input_option']["format"]="{%l}{%yp}{%mp}{%dp}{%datefix}";
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"textarea"),function ($r, $tc) {
            $tc['modifier'][] ='|nl2br';
            $tc['input_option']["cols"] ="40";
            $tc['input_option']["rows"] ="5";
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"text"),function ($r, $tc) {
            $tc['input_option']["size"] ="40";
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"password"),function ($r, $tc) {
            $tc['modifier'][] ='|hidetext';
            $tc['input_option']["size"] ="40";
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"file"),function ($r, $tc) {
            $group =$tc['group'] ? $tc['group'] : "public";
            $tc['modifier'][] ='|userfile:"'.$group.'"';
            $tc['input_option']['group='] =$group;
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"checkbox"),function ($r, $tc) {
            $tc['modifier'][] ='|selectflg';
            $tc['input_option']["value"] ="1";
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"select"),function ($r, $tc) {
            $tc['modifier'][] ='|select:"'.$tc['list'].'"';
            $tc['input_option']['options'] =$tc['list'];
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"radioselect"),function ($r, $tc) {
            $tc['modifier'][] ='|select:"'.$tc['list'].'"';
            $tc['input_option']['options'] =$tc['list'];
        	return $tc;
		});
        $r->add_filter("col_init",array("type"=>"checklist"),function ($r, $tc) {
            $tc['modifier'][] ='|select:"'.$tc['list'].'"|@tostring:" "';
            $tc['input_option']['options'] =$tc['list'];
        	return $tc;
		});
        $r->add_filter("col_init",array(),function ($r, $tc) {
            $tc['input_class'][] ="input-".$tc['type'];
            
            // DB上の定義用の参照設定
            if ($tc['def']['type']) { 
                $tc["ref"]["_def"] =1;
            }
            // 入力/表示用の参照設定
            if ($tc['type']) {
                $tc["ref"]["_input"] =1;
                $tc["ref"]["_show"] =1;
            }
            
        	return $tc;
		});
        $r->add_filter("col_init_after",array("input"=>false),function ($r, $tc) {
            $tc['html']['input'] ='{{input type=""}}';
        });
        $r->add_filter("col_init_after",array("show"=>false),function ($r, $tc) {
            $tc['html']['show'] ='';
        });
    }