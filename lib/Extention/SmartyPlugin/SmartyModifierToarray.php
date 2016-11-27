<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierToarray
{
    /**
     * @overload
     */
    function callback ($value)
    {
        if (is_string($value) && $unserialized = unserialize($value)) {
            $value = $unserialized;
        } elseif ( ! is_array($value)) {
            $value = array();
        }
        return $value;
    }
}