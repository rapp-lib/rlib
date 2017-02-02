<?php
namespace R\Lib\Core\Middleware;

use R\Lib\Core\Contract\Middleware;

class ConsoleResultFallback implements Middleware
{
    public function handler ($next)
    {
        $response = $next();
        if ($response) {
            return $response;
        }
        return app()->response->output(array(
            "type" => "success",
        ));
    }
}
