<?php
namespace R\Lib\Webapp;
/*
SAMPLE
-------------------------------------
    route()->setCurrent("www", "/index.html");
    route()->getWebroot("www")->setAttrs(array(
        "docroot_dir" => "/var/www/html",
    ));
    route()->getWebroot("www")->setAttrs(array(
        "domain" => "www.example.com",
        "docroot_url" => "",
        "webroot_url" => "/system",
    ));
    route()->getWebroot("www")->addRouting(array(
        "index.index" => "/index.html",
        "index.test" => "/test.html",
    ));
    $url = route("/test.html")->getUrl(array("back"=>1));
    route(".test")->getFile();
 */
class RouteManager
{
    private static $instance = null;
    private $webroots = array();
    private $current_route = null;
    private $current_webroot_name = null;
    /**
     * インスタンスを取得
     */
    public static function getInsatance ($route_name=null)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Route;
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
            $webroot_name = $this->current_webroot_name;
        }
        if ( ! $this->webroots[$webroot_name]) {
            $this->webroots[$webroot_name] = new Webroot($this, $webroot_name);
        }
        return $this->webroots[$webroot_name];
    }
    /**
     * 現在アクセスされているWebapp/Routeを設定する
     */
    public function setCurrent ($current_webroot_name, $current_route_name)
    {
        $this->current_webroot_name = $current_webroot_name;
        $this->current_route = $this->getWebroot($current_webroot_name)->getRoute($current_route_name);
    }
}
/**
 *
 */
class Webroot
{
    private $route_manager;
    private $webroot_name;
    /**
     * page => pathの対応
     */
    private $routing = array();
    /**
     * 設定値
     *      "domain" => "www.example.com",
     *      "docroot_dir" => "/var/www/html",
     *      "docroot_url" => "",
     *      "webroot_url" => "/system",
     */
    private $attrs = array();
    /**
     *
     */
    public function __construct ($route_manager, $webroot_name)
    {
        $this->route_manager = $route_manager;
        $this->webroot_name = $webroot_name;
        $this->attrs = $attrs;
    }
    /**
     * Routeインスタンスを作成
     */
    public function getRoute ($route_name=null)
    {
        return new Route($this, $route_name);
    }
    /**
     * 設定値の設定
     */
    public function setAttrs ($attrs)
    {
        foreach ($attrs as $key => $value) {
            $this->attrs[$key] = $value;
        }
    }
    /**
     * 設定値の取得
     */
    public function getAttr ($key, $required=false)
    {
        if ($required && ! isset($this->attrs[$key])) {
            repore_error("設定値が未設定です",array(
                "key" => $key,
                "webroot_name" => $this->webroot_name,
                "attrs" => $this->attrs,
            ));
        }
        return $this->attrs[$key];
    }
    /**
     * RoutingTableにpage:pathの対応を追加
     */
    public function addRouting ($routing)
    {
        foreach ($routing as $page => $path) {
            $this->routing[$page] = $path;
        }
    }
}
/**
 *
 */
class Route
{
    private $webroot;
    private $path = null;
    private $page = null;
    private $embed_path = null;
    private $embed_values = null;

    /**
     *
     */
    public function __construct ($webroot, $route_name)
    {
        $this->webroot = $webroot;
        // "path:","/"で始まる場合Pathと判断する
        if (preg_match('!^(:?path:)?(/.*)$!',$route_name,$match)) {
            $this->path = $match[1];
        // "page:","XXX.XXX",".XXX"形式である場合Pageと判断する
        } elseif (preg_match('!^(:?page:)?([a-zA-Z0-9_]+)?\.([a-zA-Z0-9_]+)$!',$route_name,$match)) {
            $controller_name = $match[1];
            $action_name = $match[2];
            // 相対Pageで記述されている場合、RouteManager::getCurrentRoute()から補完
            if (strlen($controller_name)==0) {
                //TODO:相対Page解決
            }
            $this->page = $controller_name.".".$action_name;
        // "url:"で始まる場合URLと判断する
        } elseif (preg_match('!^(:?url:)(.*)$!',$route_name,$match)) {
            $url = $match[1];
            //TODO: embed解析
            $this->path = null;
            $this->embed_path = null;
            $this->embed_values = null;
        // "file:"で始まる場合ファイル名と判断する
        } elseif (preg_match('!^(:?file:)(.*)$!',$route_name,$match)) {
            $file = $match[1];
            //TODO: docrootdir+webroot_urlを削ってPathに変換する
            $this->path = null;
            // 変換できない領域のファイルであればエラー
            if ( ! isset($this->path)) {
                report_warning("DocumentRoot外のファイルはRouteを定義できません",array(
                    "webroot" => $this->webroot,
                    "file" => $file,
                ));
            }
        }
    }
    /**
     *
     */
    public function getPage ()
    {
        return $page;
    }
    /**
     *
     */
    public function getPath ()
    {
        return $page;
    }
    /**
     * 埋め込みパラメータ付きURLかどうか
     */
    public function hasEmbed ()
    {
        return isset($this->embed_path);
    }
    /**
     * URL中の埋め込み情報を取得
     */
    public function getEmbed ()
    {
        return array("path"=>$this->embed_path, "values"=>$this->embed_values);
    }
    /**
     *
     */
    public function getFile ()
    {
        return $page;
    }
    /**
     *
     */
    public function getUrl ()
    {
        return $url;
    }
    /**
     *
     */
    public function getHttpUrl ()
    {
        return $url;
    }
    /**
     *
     */
    public function getSecureUrl ()
    {
        return $url;
    }
}
