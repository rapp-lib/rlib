<?php

    /**
     *
     */
    function rapper_mod_basic_list_option ($r) {
        
        // ListOptionsの展開
        $r->add_filter("list_deploy",function ($r, $list_option) {
            $src =$r->fetch_template("/list/ProductPriceList.class.php", array("list_option"=>$list_option));
            $r->deploy_file("/app/list/".str_camelize($tc["list"])."List.class.php", $src);
        });
    }