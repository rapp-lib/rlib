<?php
namespace R\Lib\Auth\Middleware;

use R\Lib\Core\Contract\Middleware;

class AuthCheck implements Middleware
{
    public function handler ($next)
    {
        $controller = app()->router->getCurrentRoute()->getController();
        try {
            $auth = $controller->getAuthenticate();
            $auth_result = app()->auth->authenticate($auth["access_as"], $auth["priv_required"]);
            if ( ! $auth_result) {
                report_error("認証エラー時の転送処理が必要",array(
                    "auth_info" => $auth,
                    "route" => $route,
                ));
            }
        } catch (R\Lib\Auth\AuthRequiredException $e) {
            return $e->getResponse();
        }
    }
}