<?php
namespace R\Lib\Extention;

class SmartyPluginLoader
{
    public static function getCallback ($name)
    {
        list($type, $name) = explode(".",$name);
        // @deprecated 関数による定義の探索
        $plugin_file = __DIR__."/SmartyPlugin/Regacy/".$type.".".$name.".php";
        if (file_exists($plugin_file)) {
            require_once($plugin_file);
            return "smarty_".$type."_".$name;
        }
        $callback_method = "smarty_".$type;
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
        return true;
    }
}