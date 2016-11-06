<?php
namespace R\Lib\Route;

/*
SAMPLE
-------------------------------------
    route()->setCurrent("www", "/index.html");
    route()->getWebroot("www")->setAttrs(array(
        "docroot_dir" => "/var/www/html",
    ));
    route()->getWebroot("www")->setAttrs(array(
        "domain_name" => "www.example.com",
        "webroot_url" => "/system",
    ));
    route()->getWebroot("www")->addRouting(array(
        "index.index" => "/index.html",
        "index.test" => "/test.html",
    ));
    $url = route("/test.html")->getUrl(array("back"=>1));
    route(".test")->getFile();
 */

/**
 *
 */
class RouteManager
{
    private static $instance = null;
    private $webroots = array();
    private $current_route = null;
    /**
     * インスタンスを取得
     */
    public static function getInstance ($route_name=false)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new RouteManager;
        }
        return $route_name !== false
            ? self::$instance->getWebroot()->getRoute($route_name)
            : self::$instance;
    }
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
    public function setCurrentRoute ($route)
    {
        $this->current_route = is_string($route) ? $this->getWebroot()->getRoute($route) : $route;
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        return $this->current_route;
    }
}

