<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierPageToUrl
{
    /**
     * PageからURLを得る
     */
    public function callback ($page, $url_params=array(), $anchor=null)
    {
        if (preg_match('!^\.(.*)$!', $page, $match)) {
            $request_page_id = app()->http->getServedRequest()->getUri()->getPageId();
            $part = explode(".", $request_page_id, 2);
            $page = $part[0].".".($match[1] ?: $part[1]);
        }
        $uri = app()->http->getServedRequest()->getUri()->getWebroot()->uri("id://".$page, $url_params, $anchor)->getAbsUriString();
        return "".$uri;
    }
}
