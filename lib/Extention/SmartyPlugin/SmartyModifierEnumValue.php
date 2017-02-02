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
    function callback ($key ,$enum_set_name, $parent_key=null)
    {
        $enum = app()->enum($enum_set_name, $parent_key);
        if (is_array($key)) {
            $values = array();
            foreach ($key as $i => $akey) {
                $values[$i] = $enum[$akey];
            }
        } else {
            return $enum[$key];
        }
    }
}
