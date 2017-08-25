<?php
namespace R\Lib\Http;
use R\Lib\Asset\AssetManager;

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
            $this->router = new Router($this, (array)$this->config["routes"]);
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
    public function getAssets ()
    {
        if ( ! $this->assets) {
            $this->assets = new AssetManager;
            foreach ($this->config["assets_catalog_uris"] as $uri) {
                $this->assets->loadAssetCatalog($this->uri($uri));
            }
        }
        return $this->assets;
    }

// -- パッケージ内でのみ利用

    public function dispatch ($request, $deligate)
    {
        $stack = (array)$this->config["middlewares"];
        $stack[] = $deligate;
        ksort($stack);
        $stack = array_values($stack);
        $dispatcher = new \mindplay\middleman\Dispatcher($stack);
        $response = $dispatcher->dispatch($request);
        return $response;
    }
    /**
     * ServerRequest::__construct内で設定を反映するために使う
     * @private
     */
    public function updateByRequest ($docroot_dir, $request_uri)
    {
        if ( ! $this->getBaseUri()->getAuthority() && $request_uri) {
            $this->base_uri = $this->base_uri
                ->withHost($request_uri->getHost())
                ->withPort($request_uri->getPort() ?: 80)
                ->withScheme($request_uri->getScheme())
                ->withUserInfo($request_uri->getUserInfo());
        }
        if ( ! $this->config["base_dir"] && $docroot_dir) {
            $this->config["base_dir"] = $docroot_dir.$this->base_uri->getPath();
        }
    }

// --

    public function __report()
    {
        return array(
            "base_uri"=>"".$this->getBaseUri(),
        );
    }
}
