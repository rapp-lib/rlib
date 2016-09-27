<?php

namespace R\Lib\Rapper\Mod;

/**
 * コンフィグファイルに関するschema/deployの生成
 */
class InitConfigFile extends BaseMod {

    /**
     *
     */
    public function install () {

        $r =$this->r;

        // init.deploy
        // ->{id=config_file.xxx ,dest_file=/config/xxx.config.php, data_type=php_tmpl}
        $r->add_filter("init.deploy",array(),function($r, $d) {

            $config_files =array(
                "routing_config" => array("file" =>"routing.config.php"),
                "routing_config" => array("file" =>"label.config.php"),
                "routing_config" => array("file" =>"auth.config.php"),
                "routing_config" => array("file" =>"install.sql"),
            );
            foreach ($config_files as $config_file) {
                $r->deploy("config_file.".$config_file["_id"],array(
                    "data_type" =>"php_tmpl",
                    "tmpl_file" =>"config_file/".$config_file["file"],
                    "dest_file" =>"app/config/".$config_file["file"],
                ));
            }
        });
    }
}