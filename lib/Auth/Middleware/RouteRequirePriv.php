<?php
namespace R\Lib\Auth\Middleware;

use R\Lib\Core\Contract\Middleware;

class RouteRequirePriv implements Middleware
{
    public function handler ($next)
    {
        $priv_required = app()->router->getCurrentRoute()->getController()->getPrivRequired();
        $response = app()->auth()->requirePriv($priv_required);
        if ($response) {
            return $response;
        }
        return $next();
    }
}