<?php

define("CAKE_DIR",dirname(__FILE__));
define("DS","/");
define("APP",constant("CAKE_DIR")."/__App/");

class App {
    public static $class_locations =array();
    public static $ignore_classes =array(
        "View" =>true,
    );
    public static function uses ($class_name, $dir) {
        self::$class_locations[$class_name] =$dir;
        if ( ! self::$ignore_classes[$class_name]) { 
            require_once(constant("CAKE_DIR")."/".$dir."/".$class_name.".php");
        }
    }
    public static function location ($class_name) {
        return self::$class_locations[$class_name];
    }    
}

class Object {
    public function __construct () {}
}

class Configure {
    public function read () {}
}

class Cache {
    public function read () {}
    public function write () {}
}

class Model {
    public function isVirtualField () { return false; }
}

class CakeBaseException extends RuntimeException {}

class CakeException extends CakeBaseException {
	public function __construct($message, $code = 500) {
        $message =is_array($message) ? print_r($message,true) : $message;
		parent::__construct($message, $code);
    }
}

class MissingDatasourceConfigException extends CakeException {}
    
class MissingConnectionException extends CakeException {}

if ( ! function_exists('pluginSplit')) {
    function pluginSplit($name, $dotAppend = false, $plugin = null) {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name, 2);
            if ($dotAppend) {
                $parts[0] .= '.';
            }
            return $parts;
        }
        return array($plugin, $name);
    }
}