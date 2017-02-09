<?php
namespace R\Lib\Core\Middleware;

use R\Lib\Core\Contract\Middleware;

class JsonResponseFallback implements Middleware
{
    public function handler ($next)
    {
        $response = $next();
        if ($response) {
            return $response;
        }
        $route = app()->router->getCurrentRoute();
        return app()->response->output(array(
            "type" => "json",
            "vars" => $route->getController()->getVars(),
        ));
    }
}
