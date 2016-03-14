<?php

    /**
     *
     */
    function rapper_mod_basic_config_file ($r) {
        
        // configファイルリスト初期化設定
        $r->add_filter("config_list_init",array(), function ($r, $config_list) {
            $config_list[] =array("name"=>"routing.config.php");
            $config_list[] =array("name"=>"label.config.php");
            $config_list[] =array("name"=>"auth.config.php");
            $config_list[] =array("name"=>"install.sql");
            return $config_list;
        });
        
        $r->add_filter("config_deploy",array(), function ($r, $config) {
            // config展開
            $src =$r->fetch_template("/app/config/".$config["name"], array("config"=>$config));
            $r->deploy_file("/app/config/_".$config["name"], $src);
        });
    }