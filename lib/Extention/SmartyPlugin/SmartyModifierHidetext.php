<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierHidetext
{
    /**
     * @overload
     */
    function callback ($string)
    {
        return str_repeat("*",strlen($string));
    }
}