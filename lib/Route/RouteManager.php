<?php
namespace R\Lib\Route;

use R\Lib\Core\Contract\InvokableProvider;

class RouteManager implements InvokableProvider
{
    /**
     * @override InvokableProvider
     * Routeインスタンスを取得
     */
    public function invoke ($route_name)
    {
        return $this->getWebroot()->getRoute($route_name);
    }
    private $webroots = array();
    private $current_route = null;
    /**
     * Routeインスタンスを取得
     */
    public function getWebroot ($webroot_name=false)
    {
        if ($webroot_name===false) {
            if ($current_route = $this->getCurrentRoute()) {
                return $current_route->getWebroot();
            } else {
                report_error("CurrentRouteが未設定です");
            }
        }
        if ( ! $this->webroots[$webroot_name]) {
            $this->webroots[$webroot_name] = new Webroot($this, $webroot_name);
        }
        return $this->webroots[$webroot_name];
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        if ( ! isset($this->current_route)) {
            $webroot_name = app()->config("router.current_webroot");
            $url = app()->config("router.current_url");
            $this->current_route = $this->getWebroot($webroot_name)->getRoute("url:".$url);
        }
        return $this->current_route;
    }
    /**
     * 現在アクセスされているRouteに関係する処理を実行する
     */
    public function execCurrentRoute ()
    {
        $middleware_config = app()->config("router.middleware");
        if ( ! is_array($middleware_config)) {
            report_error("RouterのMiddleware設定が不正です",array(
                "router.middleware" => $middleware_config,
            ));
        }
        $callback = function () {
            return app()->router->getCurrentRoute()->getController()->execAct();
        };
        $callback = app()->middleware->apply($callback, $middleware_config);
        return call_user_func($callback);
    }
    /**
     * Routeに対応するControllerインスタンスを取得する
     */
    public function getRouteController ($route)
    {
        $page = $route->getPage();
        if ( ! isset($this->controllers[$page])) {
            if ( ! $page) {
                report_error("Routeに対応するPage設定がありません",array(
                    "route" => $route,
                ));
            }
            list($controller_name, $action_name) = explode('.',$page,2);
            $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
            if ( ! class_exists($controller_class)) {
                report_error("Pageに対応するControllerクラスの定義がありません",array(
                    "page" => $page,
                ));
            }
            $this->controllers[$page] = new $controller_class($controller_name, $action_name);
        }
        return $this->controllers[$page];
    }
}
