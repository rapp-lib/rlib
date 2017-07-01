<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierPathToUrl
{
    /**
     * PathからURLを得る
     */
    public function callback ($page, $url_params=array(), $anchor=null)
    {
        $uri = app()->http->getWebroot()->uri("path://".$path, $url_params, $anchor)->getAbsUriString();
        return "".$uri;
    }
}
