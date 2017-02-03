<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierEnumValue
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
            return implode(" ",$values);
        } else {
            return $enum[$key];
        }
    }
}
