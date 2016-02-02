<?php

    /**
     *
     */
    function rapper_mod_basic_model ($r) {
        
        $r->add_filter("table_deploy",array("nomodel"=>false),function ($r, $t) {
            // Modelの展開
            $src =$r->fetch_template("/model/ProductModel.class.php", array("t"=>$t));
            $r->deploy_file("/app/model/".str_camelize($t["name"])."Model.class.php", $src);
        });
    }