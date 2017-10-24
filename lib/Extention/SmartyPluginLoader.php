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
        return true;
    }
    /**
     * Smarty::registerDefaultPluginHandlerに登録するメソッド
     * プラグイン読み込み処理
     */
    public static function pluginHandler ($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        $ext = \R\Lib\Extention\SmartyPluginLoader::getCallback($type.".".$name);
        if ( ! is_callable($ext)) return false;
        $callback = $ext;
        return true;
    }
}