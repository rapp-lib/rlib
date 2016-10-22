<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierTostring
{
    /**
     * @overload
     */
    function callback ($value, $delim)
    {
        return is_array($value)
                ? implode($value,$delim)
                : (string)$value;
    }
}