<?php

/**
*
*/
class CakeLoader {
    
    /**
    *
    */
    public function __construct() {
        
        require_once(dirname(__FILE__)."/../cake/rlib_cake.php");
    }
    
    /**
    *
    */
    public function get_cake_datasource ($ds_name, $connect_info) {
        
        if ($connect_info["driver"]) {
        
            require_once(LIBS.'/model/datasources/dbo/'
                    .'dbo_'.$connect_info["driver"].'.php');
        }
        
        ConnectionManager::create($ds_name,$connect_info);
        return ConnectionManager::getDataSource($ds_name);
    }
}