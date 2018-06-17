<?php
namespace R\Lib\Http;
// use R\Lib\Asset\AssetManager;

class Webroot
{
    private $config;
    private $base_uri;
    private $router;
    public function __construct (array $webroot_config)
    {
        $this->config = $webroot_config;
    }
    public function uri ($uri, $query_params=array(), $fragment="")
    {
        return new Uri($this, $uri, $query_params, $fragment);
    }
    public function getRouter ()
    {
        if ( ! isset($this->router)) {
            $this->router = new Router($this);
        }
        return $this->router;
    }
    public function getBaseUri ()
    {
        if ( ! isset($this->base_uri)) {
            $this->base_uri = $this->uri($this->config["base_uri"] ?: "");
        }
        return $this->base_uri;
    }
    public function getBaseDir ()
    {
        if ( ! isset($this->config["base_dir"])) {
            report_error("base_dirが未設定",array(
                "config" => $this->config,
            ));
        }
        return $this->config["base_dir"];
    }
    // public function getAssets ()
    // {
    //     if ( ! $this->assets) {
    //         $this->assets = new AssetManager;
    //         foreach ($this->config["assets_catalog_uris"] as $uri) {
    //             $this->assets->loadAssetCatalog($this->uri($uri));
    //         }
    //     }
    //     return $this->assets;
    // }
    /**
     * ServerRequest::__construct内で設定を反映するために使う
     * @access private
     */
    public function updateByRequest ($docroot_dir, $request_uri)
    {
        if ( ! $this->getBaseUri()->getAuthority() && $request_uri) {
            if ($request_uri->getHost()) $this->base_uri = $this->base_uri->withHost($request_uri->getHost());
            if ($request_uri->getPort()) $this->base_uri = $this->base_uri->withPort($request_uri->getPort());
            if ($request_uri->getScheme()) $this->base_uri = $this->base_uri->withScheme($request_uri->getScheme());
            if ($request_uri->getUserInfo()) $this->base_uri = $this->base_uri->withUserInfo($request_uri->getUserInfo());
        }
        if ( ! $this->config["base_dir"] && $docroot_dir) {
            $this->config["base_dir"] = $docroot_dir.$this->base_uri->getPath();
        }
    }

// -- 設定値の取得

    public function getRoutesConfig ()
    {
        $routes = (array)$this->config["routes"];
        $routes = array_merge($routes, (array)app()->config["http.global.routes"]);
        return $routes;
    }
    public function getMiddlewareStack ()
    {
        $stack = (array)$this->config["middlewares"];
        $stack = array_merge($stack, (array)app()->config["http.global.middlewares"]);
        return $stack;
    }
    public function getControllerClass ($controller_name)
    {
        $classes = (array)$this->config["controller_class"];
        $classes = array_merge($classes, (array)app()->config["http.global.controller_class"]);
        if ($classes[$controller_name]) return $classes[$controller_name];
        return 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
    }

// --

    public function __report()
    {
        return array(
            "base_uri"=>"".$this->getBaseUri(),
        );
    }
}
