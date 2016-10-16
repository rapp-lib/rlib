<?php
namespace R\Lib\Core;

class ExtentionManager
{
    /**
     * ExtentionCallbackを取得する
     */
    public static function getCallback ($group, $name)
    {
        $callback = null;
        // R\Lib\Extention\[Group]Loader::getCallback()があれば呼び出す
        $class_name = "R\\Lib\\Module\\ExtentionLoader\\".str_camelize($group)."Loader";
        if (class_exists($class_name) && method_exists($class_name, "getCallback")) {
            $callback = call_user_func(array($class_name,"getCallback"),$name);
            if ($callback) {
                return $callback;
            }
        }
        $base_class_name = str_camelize($group)."\\".str_camelize($name).str_camelize($group);
        // R\Lib\Extention\[Group]\[Name][Group]を探索
        if (class_exists($class_name = "R\\LibExtention\\".$base_class_name)
            && method_exists($class_name, "callback")) {
            return array($class_name, "callback");
        // R\App\Extention\[Group]\[Name][Group]を探索
        } elseif (class_exists($class_name = "R\\Extention\\".$base_class_name)
            && method_exists($class_name, "callback")) {
            return array($class_name, "callback");
        }
        report_error("Extentionが読み込めません",array(
            "group" => $group,
            "name" => $name,
        ));
    }
}