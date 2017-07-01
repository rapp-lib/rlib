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
        if ( ! $this->config["base_dir"]) {
            report_error("base_dirが未設定");
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

    /**
     * ServerRequest::dispatch内で設定を引き出すために使う
     * @private
     */
    public function getMiddlewareStack ()
    {
        return (array)$this->config["middlewares"];
    }
    /**
     * ServerRequest::__construct内で設定を反映するために使う
     * @private
     */
    public function updateByRequest ($request, $request_uri)
    {
        if ( ! $this->getBaseUri()->getAuthority()) {
            $this->base_uri = $this->base_uri
                ->withHost($request_uri->getHost())
                ->withPort($request_uri->getPort() ?: 80)
                ->withScheme($request_uri->getScheme())
                ->withUserInfo($request_uri->getUserInfo());
        }
        if ( ! $this->config["base_dir"]) {
            $server = $request->getServerParams();
            $this->config["base_dir"] = $server["DOCUMENT_ROOT"].$this->base_uri->getPath();
        }
    }
}