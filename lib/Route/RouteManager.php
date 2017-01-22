<?php
namespace R\Lib\Route;

/**
 *
 */
class RouteManager
{
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
     * 現在アクセスされているRouteを設定する
     */
    public function setCurrent ($webroot_name, $route_name)
    {
        $this->current_route = $this->getWebroot($webroot_name)->getRoute($route_name);
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        return $this->current_route;
    }
}

