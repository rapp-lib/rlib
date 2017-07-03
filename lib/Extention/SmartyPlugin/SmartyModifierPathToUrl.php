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
    public function callback ($path, $url_params=array(), $anchor=null)
    {
        $uri = app()->http->getServedRequest()->getUri()
            ->getPageAction()->getController()->uri("path://".$path, $url_params, $anchor)->getAbsUriString();
        return "".$uri;
    }
}
