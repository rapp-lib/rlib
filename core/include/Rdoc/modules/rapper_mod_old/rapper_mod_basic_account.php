<?php

    /**
     *
     */
    function rapper_mod_basic_account ($r) {
        
        $r->add_filter("account_deploy",function ($r, $account) {
    		// Contextの展開
            $src =$r->fetch_template("/login/MemberAuthContext.class.php", array("account"=>$account));
            $r->deploy_file("/app/context/".str_camelize($c["account"])."AuthContext.class.php", $src);
        });
    }