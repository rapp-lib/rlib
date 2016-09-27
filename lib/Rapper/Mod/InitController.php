<?php

namespace R\Lib\Rapper\Mod;

/**
 * Controllerに関するschema/deployの生成
 */
class InitController extends BaseMod {

    /**
     *
     */
    public function install () {

        $r =$this->r;

        // type=index→actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"index")),function($r,$c)
        {
            // 空白ページ
            $c["action"]["index"] = array(
                "name" =>"index",
            );
        });

        // type=master→actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"master")),function($r,$c)
        {
            if ($c["usage"] != "form") {
                // 表示
                $c["action"]["list"] = array(
                    "name" =>"list",
                );
                $c["action"]["detail"] = array(
                    "name" =>"detail",
                );
                // CSV出力
                if ($c["use_csv"]) {
                    $c["action"]["export_csv"] = array(
                        "name" =>"export_csv",
                    );
                }
            }

            if ($c["usage"] != "view") {
                // フォーム入力
                $c["action"]["entry"] = array(
                    "name" =>"entry",
                );
                if ($c["use_csv"]) {
                    // CSV登録
                    $c["action"]["import_csv"] = array(
                        "name" =>"import_csv",
                    );
                }
            }

            if ($c["usage"] != "form" && $c["usage"] != "view") {
                // 削除
                $c["action"]["delete"] = array(
                    "name" =>"delete",
                );
            }
        });

        // type=login→actionの補完
        $r->add_filter("init.schema.controller",array("cond"=>array("type"=>"login")),function($r,$c)
        {
            // ログイン
            $c["action"]["login"] = array(
                "name" =>"login",
            );
        });

        // init.deploy [controller]
        // ->action_method.xxx.xxx
        // ->html/xxx/xxx.html
        // ->controller_class.xxx
        $r->add_filter("init.deploy.controller",array(),function($r, $c) {

            foreach ($c->getActions() as $a) {

                $r->deploy("action_method.".$c->getId().".".$a->getId(),array(
                    "data_type" =>"php_tmpl",
                    "tmpl_file" =>"controller/action/".$a->getType().".php",
                    "assign" =>array("c" =>$c, "a" =>$a),
                ));

                foreach ($a->getPages() as $page) {

                    if ($page->hasHtml()) {
                        $r->deploy("page_html.".$_id,array(
                            "data_type" =>"php_tmpl",
                            "tmpl_file" =>"html/action/".$a->getType()."/".$page->getType().".html",
                            "dest_file" =>"html/".$page->getPath(),
                            "assign" =>array("c" =>$c, "a" =>$a, "page" =>$page),
                        ));
                    }
                }
            }

            $r->deploy("controller_class.".$c->getId(),array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"controller/XxxController.class.php",
                "dest_file" =>"app/controller/".$c->getClassName().".class.php",
                "assign" =>array("c" =>$c),
            ));
        });
    }
}