<?php

namespace R\Lib\Rapper\Mod;

/**
 * Wrapperに関するschema/deployの生成
 */
class InitWrapper extends BaseMod {

    /**
     *
     */
    public function install () {

        $r =$this->r;

        // init.schema [controller]
        // ->[wrapper]
        $r->add_filter("init.schema.controller",array("cond"=>array("wrapper")),function($r, $c) {

            $_id =$c["wrapper"];
            $r->schema("wrapper.".$_id,array(
                "_id" =>$_id,
            ));
        });

        // init.deploy [wrapper.*]
        // ->html/element/xxx_wrapper_head.html
        // ->html/element/xxx_wrapper_foot.html
        $r->add_filter("init.deploy.wrapper",array(),function($r, $wrapper) {

            $r->deploy("wrapper.".$wrapper["_id"]."_header",array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"html/element/xxx_header.html",
                "dest_file" =>"html/element/".$wrapper["_id"]."_header.html",
            ));
            $r->deploy("wrapper.".$wrapper["_id"]."_footer",array(
                "data_type" =>"php_tmpl",
                "tmpl_file" =>"html/element/xxx_footer.html",
                "dest_file" =>"html/element/".$wrapper["_id"]."_footer.html",
            ));
        });

    }
}