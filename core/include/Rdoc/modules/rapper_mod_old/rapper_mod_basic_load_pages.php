<?php

    /**
     *
     */
    function rapper_mod_basic_load_pages ($r) {
        
        // controller初期化設定
        $r->add_filter("controller_init",array("type"=>"master","action"=>false),function ($r, $c) {
            $c["action"]["index"] =array(
                "label" =>"INDEX",
                "type" =>"redirect",
                "path" =>"/product_master/product_master.index.html",
                "redirect_to" =>array("page" =>".list"),
            );
            $c["action"]["list"] =array(
                "label" =>"一覧",
                "type" =>"list",
                "menu_links" =>array(
                    array("page"=>".edit","label"=>"新規登録"),
                    array("page"=>".csv_export"),
                    array("page"=>".csv_import"),
                ),
                "item_links" =>array(
                    array("page"=>".edit","label"=>"編集"),
                    array("page"=>".delete"),
                ),
            );
            $c["action"]["edit"] =array(
                "label" =>"編集",
                "type" =>"edit",
                "redirect_to" =>array("page" =>".view_list"),
            );
            $c["action"]["delete"] =array(
                "label" =>"削除",
                "type" =>"delete",
                "redirect_to" =>array("page" =>".view_list"),
            );
            $c["action"]["csv_import"] =array(
                "label" =>"CSVインポート",
                "type" =>"csv_import",
                "redirect_to" =>array("page" =>".view_list"),
            );
            $c["action"]["csv_export"] =array(
                "label" =>"CSVエクスポート",
                "type" =>"csv_export",
                "redirect_to" =>array("page" =>".view_list"),
            );
            return $c;
        });
        $r->add_filter("controller_init",array("type"=>"login", "account"=>false),function ($r, $c) {
            report_error("controller(.type=login)は.account=の設定が必須",array(
                "controller" =>$c["name"],
            ));
        });
        $r->add_filter("controller_init",array("type"=>"login","action"=>false),function ($r, $c) {
            $c["action"]["index"] =array(
                "type" =>"redirect",
                "redirect_to" =>array("page" =>".login_form"),
            );
            $c["action"]["login"] =array(
                "label" =>"ログイン",
                "type" =>"login",
                "redirect_to" =>array("page" =>"index"),
            );
            $c["action"]["logout"] =array(
                "label" =>"ログアウト",
                "type" =>"logout",
                "redirect_to" =>array("page" =>"index"),
            );
            return $c;
        });
        
        // controller展開設定
        $r->add_filter("controller_deploy",array("wrapper"=>true),function ($r, $c) {
            // header展開
            $src =$r->fetch_template("/html/element/default_header.html", array("c"=>$c));
            $r->deploy_file("html/element/".$c["wrapper"].'_header.html', $src);
            // footer展開
            $src =$r->fetch_template("/html/element/default_footer.html", array("c"=>$c));
            $r->deploy_file("html/element/".$c["wrapper"].'_footer.html', $src);
        });
        $r->add_filter("controller_deploy",array(),function ($r, $c) {
            // Controller展開
            $src =$r->fetch_template("/login/MemberLoginController.class.php", array("c"=>$c));
            $r->deploy_file("/app/controller/".str_camelize($c["name"])."Controller.class.php", $src);
        });
        $r->add_filter("action_deploy",array("type"=>"redirect"),function ($r, $a) {
            // template展開
            $src =$r->fetch_template("login/member_login.".$a["name"].".html", array("a"=>$a));
            $r->deploy_file("/html/".$c["name"]."/".$c["name"].".".$a["name"].".html", $src);
        });
    }