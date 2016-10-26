<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierEnum
{
    /**
     * @overload
     */
    function callback ($key ,$enum_name, $group=null) {
        $enum = enum($enum_name, $group);
        return $enum[$key];
    }
}
