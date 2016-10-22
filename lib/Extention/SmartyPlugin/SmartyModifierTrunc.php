<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierTrunc
{
    /**
     * @overload
     */
    function callback ($value, $length, $append="...")
    {
        return mb_strlen($value)>$length
                ? mb_substr($value,0,$length).$append
                : $value;
    }
}