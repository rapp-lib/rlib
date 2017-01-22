<?php
namespace R\Lib\Core\Util;

class String
{
    /**
     * 命名規則の変更 xxx_xxx->XxxXxx
     */
    public static function toCamelCase ($str)
    {
        return str_camelize($str);
    }
    /**
     * 命名規則の変更 XxxXxx->xxx_xxx
     */
    public static function toSnakeCase ($str)
    {
        return str_underscore($str);
    }
    /**
     * ランダムな文字列を取得
     */
    public static function random ($length=16)
    {
        return rand_string($length);
    }
}
