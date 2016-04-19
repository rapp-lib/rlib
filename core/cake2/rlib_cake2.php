<?php

define("CAKE_LIB_DIR",dirname(__FILE__));
$ref =new ReflectionClass("App");
var_dump($ref->getFileName());
class App {
    public static $class_locations =array();
    public static function uses ($class_name, $dir) {
        self::$class_locations[$class_name] =$dir;
        require_once(constant("CAKE_LIB_DIR")."/".$dir."/".$class_name.".php");
    }
    public static function location ($class_name) {
        return self::$class_locations[$class_name];
    }
    
}

class DATABASE_CONFIG {}

require_once(constant("CAKE_LIB_DIR").'/Model/ConnectionManager.php');

class ConnectionManagerExtends extends ConnectionManager { 
    protected static function _init() {
        self::$config = new DATABASE_CONFIG();
        //TODO self::$configを初期化
        self::$_init = true;
    }
}