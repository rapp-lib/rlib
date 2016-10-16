<?php

/**
*
*/
class Cake2Loader {

    /**
    *
    */
    public function __construct() {

        require_once(dirname(__FILE__)."/cake2/rlib_cake2.php");
    }

    /**
    *
    */
    public function get_cake_datasource ($ds_name, $connect_info) {

        require_once(constant("CAKE_DIR").'/Model/ConnectionManager.php');

        // [Deprecated] 旧cakeとの互換処理
        if ($connect_info["driver"] && ! $connect_info["datasource"]) {

            $connect_info["datasource"] ='Database/'.str_camelize($connect_info["driver"]);
        }

        ConnectionManager::create($ds_name,$connect_info);
        return ConnectionManager::getDataSource($ds_name);
    }
}