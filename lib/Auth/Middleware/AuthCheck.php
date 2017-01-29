<?php
namespace R\Lib\Auth\Middleware;

use R\Lib\Core\Contract\Middleware;

class AuthCheck implements Middleware
{
    public function handler ($next)
    {
        $controller = app()->router->getCurrentRoute()->getController();
        $auth = $controller->getAuthenticate();
        $response = app()->auth->authenticate($auth["access_as"], $auth["priv_required"]);
        if ($response) {
            return $response;
        }
        return $next();
    }
}