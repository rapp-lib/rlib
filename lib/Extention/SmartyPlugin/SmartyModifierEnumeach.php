<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierEnumeach
{
    /**
     * @overload
     */
    function callback ($keys ,$enum_set_name, $parent_key=null)
    {
        $enum = enum($enum_set_name, $parent_key);
        if (is_string($keys) && $unserialized = unserialize($keys)) {
            $keys = $unserialized;
        } elseif ( ! is_array($keys)) {
            $keys = array();
        }
        $values = array();
        foreach ($keys as $i => $key) {
            $values[$i] = $enum[$key];
        }
        return $values;
    }
}
