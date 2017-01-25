<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;

class ExtentionLoader
{
    public function invoke ($group, $name)
    {
        return $this->getExtention($group, $name);
    }
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
            // ClassName::methodNameの形式でメソッドの定義であればそのまま返す
            if (preg_match('!^(.*?)::(.*?)$!',$name,$match)
                && class_exists($match[1]) && method_exists($match[1], $match[2])) {
                return $name;
            }
            // R\Lib\Extention\[Group]\[Name][Group]を探索
            $base_class_name = str_camelize($group)."\\".str_camelize($name).str_camelize($group);
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
            // クラス名であればそのまま返す
            if (class_exists($name) && method_exists($name, $name)) {
                return $name;
            }
            // R\Lib\Extention\[Group]\[Name][Group]を探索
            $base_class_name = str_camelize($group)."\\".str_camelize($name).str_camelize($group);
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