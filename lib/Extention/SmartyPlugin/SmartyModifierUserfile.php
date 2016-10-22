<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierUserfile
{
    /**
     * @overload
     */
    function callback ($code, $group=null)
    {
        return obj("UserFileManager")->get_url($code,$group);
    }
}