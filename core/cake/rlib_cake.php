<?php

define("DS","/");
define("LIBS",dirname(__FILE__)."/");
define("CONFIGS",dirname(__FILE__).'/_config/');

require_once(LIBS.'/object.php');
require_once(LIBS.'/set.php');
require_once(LIBS.'/string.php');
require_once(LIBS.'/inflector.php');
require_once(LIBS.'/model/connection_manager.php');
require_once(LIBS.'/model/datasources/dbo_source.php');
    
class App { 
    static function import() {} 
    function core () {} 
}

class Configure { 
    public static function read() {} 
}

class Cache { 
    function read($key, $dist="_default_") {} 
    function write($key, $value, $dist="_default_") {} 
}

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

if ( ! function_exists('getMicrotime')) {

    function getMicrotime() {

        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }
}

if ( ! function_exists('__esc')) {
    function __esc($str,$flg) {
        return $str;
    }
}