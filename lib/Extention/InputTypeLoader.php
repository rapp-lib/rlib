<?php
namespace R\Lib\Extention;

class InputTypeLoader
{
    private static $callbacks = array();
    public static function getCallback ($name)
    {
        // 旧仕様クラスの読み込み
        if (self::$callbacks[$name]) {
            return self::$callbacks[$name];
        }
        $class_name = "R\\Lib\\Extention\\InputType\\Regacy\\".str_camelize($name);
        if (class_exists($class_name)) {
            return self::$callbacks[$name] = function ($value,$params) use ($class_name) {
                $input =new $class_name($value,$params);
                $html = $input->getAssign();
                $html["formatted"] = $input->getHtml();
                return $html;
            };

        }
    }
}