<?php
namespace R\Lib\Auth\Middleware;

use R\Lib\Core\Contract\Middleware;

class RouteRequirePriv implements Middleware
{
    public function handler ($next)
    {
        $priv_required = app()->router->getCurrentRoute()->getController()->getPrivRequired();
        try {
            app()->auth()->requirePriv($priv_required);
        } catch (R\Lib\Core\Exception\ResponseException $e) {
            return $e->getResponse();
        }
        return $next();
    }
}