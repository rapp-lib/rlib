<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyFunctionWebrootUrl
{
    /**
     * {{webroot_url}}のサポート
     * @overload
     */
    public static function callback ($params, $smarty)
    {
        return webroot()->getAttr("webroot_url");
    }
}