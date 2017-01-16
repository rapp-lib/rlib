<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierStoredFileUrl
{
    /**
     * @overload
     */
    function callback ($code, $default=null)
    {
        return $code ? webroot()->getAttr("webroot_url")."/file:/".$code : $default;
    }
}
