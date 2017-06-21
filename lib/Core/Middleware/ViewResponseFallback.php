<?php
namespace R\Lib\Core\Middleware;

use R\Lib\Core\Contract\Middleware;

class ViewResponseFallback implements Middleware
{
    public function handler ($next)
    {
        $response = $next();
        if ($response) {
            return $response;
        }
        $route = app()->router->getCurrentRoute();
        if ( ! is_file($route->getFile())) {
            return app()->response->error("", 404);
        }
        return app()->response->output(array(
            "type" => "view",
            "file" => $route->getFile(),
            "vars" => $route->getController()->getVars(),
        ));
    }
}