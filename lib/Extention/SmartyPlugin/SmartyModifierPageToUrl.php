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
    public function callback ($page_id, $query_params=array(), $anchor=null)
    {
        $request_uri = app()->http->getServedRequest()->getUri();
        $page_id = $request_uri->getPageAction()->getController()->resolveRelativePageId($page_id);
        $uri = $request_uri->getWebroot()->uri(array("page_id"=>$page_id), $query_params, $anchor)
            ->withoutAuthorityInWebroot();
        return "".$uri;
    }
}
