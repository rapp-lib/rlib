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
    private $current_webroot_name = null;
    private $current_route = null;
    /**
     * インスタンスを取得
     */
    public static function getInstance ($route_name=null)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new RouteManager;
        }
        return isset($route_name)
            ? self::$instance->getWebroot()->getRoute($route_name)
            : self::$instance;
    }
    /**
     * Routeインスタンスを取得
     */
    public function getWebroot ($webroot_name=null)
    {
        if ( ! isset($webroot_name)) {
            $webroot_name = $current_webroot_name;
        }
        if ( ! $this->webroots[$webroot_name]) {
            $this->webroots[$webroot_name] = new Webroot($this, $webroot_name);
        }
        return $this->webroots[$webroot_name];
    }
    /**
     * 現在アクセスされているWebrootを設定する
     */
    public function setCurrentWebroot ($webroot)
    {
        $this->current_webroot_name = is_string($webroot) ? $this->getWebroot($webroot_name) : $webroot;
    }
    /**
     * 現在アクセスされているRouteを設定する
     */
    public function setCurrentRoute ($route)
    {
        $this->current_route = is_string($route) ? $this->getWebroot()->getRoute($route) : $route;
        $this->setCurrentWebroot($this->current_route->getWebroot());
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        return $this->current_route;
    }
}

