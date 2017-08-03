<?php
namespace R\Lib\Extention\SmartyPlugin;

class SmartyModifierUrlToPrivReq
{
    function callback ($uri)
    {
        $uri = app()->http->getServedRequest()->getUri()->getWebroot()->uri($uri);
        $priv_req = $uri->getPageAuth()->getPrivReq();
        return $priv_req;
    }
}
