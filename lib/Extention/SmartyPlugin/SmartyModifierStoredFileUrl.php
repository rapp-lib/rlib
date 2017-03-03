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
        return $code ? app()->router->getWebroot()->getConfig("webroot_url")."/file:/".$code : $default;
    }
}
