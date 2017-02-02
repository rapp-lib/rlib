<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierSelect
{
    /**
     * @overload
     */
    function callback ()
    {
        $enum = app()->enum($enum_set_name, $parent_key);
        return $enum[$key];
    }
}
