<?php
namespace R\Lib\Core;

class ExtentionManager
{
    /**
     * Extentionを取得する
     */
    public static function getExtention ($group, $name)
    {
        $callback = null;
        $class_name = "R\\Lib\\Extention\\".str_camelize($group)."Loader";
        // R\Lib\Extention\[Group]Loader::getCallback()があればcallbackを取得する
        if (class_exists($class_name) && method_exists($class_name, "getCallback")) {
            if ($callback = call_user_func(array($class_name,"getCallback"),$name)) {
                return $callback;
            }
            $base_class_name = str_camelize($group)."\\".str_camelize($name).str_camelize($group);
            // R\Lib\Extention\[Group]\[Name][Group]を探索
            if (class_exists($class_name = "R\\Lib\\Extention\\".$base_class_name)
                && method_exists($class_name, "callback")) {
                return array($class_name, "callback");
            // R\App\Extention\[Group]\[Name][Group]を探索
            } elseif (class_exists($class_name = "R\\App\\Extention\\".$base_class_name)
                && method_exists($class_name, "callback")) {
                return array($class_name, "callback");
            }
        }
        // R\Lib\Extention\[Group]Loader::getClass()があればclassを取得する
        if (class_exists($class_name) && method_exists($class_name, "getClass")) {
            if ($class = call_user_func(array($class_name,"getClass"),$name)) {
                return $class;
            }
            $base_class_name = str_camelize($group)."\\".str_camelize($name).str_camelize($group);
            // R\Lib\Extention\[Group]\[Name][Group]を探索
            if (class_exists($class_name = "R\\Lib\\Extention\\".$base_class_name)) {
                return $class_name;
            // R\App\Extention\[Group]\[Name][Group]を探索
            } elseif (class_exists($class_name = "R\\App\\Extention\\".$base_class_name)) {
                return $class_name;
            }
        }
        report_error("Extentionが読み込めません",array(
            "group" => $group,
            "name" => $name,
        ));
    }
}