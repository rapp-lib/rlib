<?php
namespace R\Lib\Extention\SmartyPlugin;

class SmartyModifierEnumValues
{
    function callback ($enum_set_name)
    {
        return app()->enum[$enum_set_name];
        // $values = array();
        // foreach (app()->enum[$enum_set_name] as $k=>$v) $values[$k] = $v;
        // return $values;
    }
}
