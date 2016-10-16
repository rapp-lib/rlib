<?php
namespace R\Lib\Module\ExtentionLoader;

class ValidationRuleLoader
{
    public static function getCallback ($name)
    {
        $callback_method = "callback".str_camelize($name);
        if (method_exists(self,$callback_method)) {
            return array(self,$callback_method);
        }
        return null;
    }
    public static function callbackRequired ($validator, $value, $rule)
    {
        if (strlen($value)) {
            return false;
        }
        return array("message"=>"必ず入力して下さい");
    }
}