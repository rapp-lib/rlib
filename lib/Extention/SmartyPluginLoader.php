<?php
namespace R\Lib\Extention;

class SmartyPluginLoader
{
    public static function getCallback ($name)
    {
        list($type, $name) = explode(".",$name);
        $callback_method = "callback";
        // Libの探索
        $class_name = 'R\\Lib\\Extention\\SmartyPlugin\\Smarty'.str_camelize($type).str_camelize($name);
        if (class_exists($class_name) && method_exists($class_name, $callback_method)) {
            return $class_name."::".$callback_method;
        }
        // Appの探索
        $class_name = 'R\\App\\Extention\\SmartyPlugin\\Smarty'.str_camelize($type).str_camelize($name);
        if (class_exists($class_name) && method_exists($class_name, $callback_method)) {
            return $class_name."::".$callback_method;
        }
        return false;
    }
}