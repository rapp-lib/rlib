<?php
namespace R\Lib\Http;

class Webroot
{
    private $config;
    private $router;
    public function __construct (array $webroot_config)
    {
        $this->config = $webroot_config;
    }
    public function getMiddlewareStack ()
    {
        return (array)$this->config["middlewares"];
    }
    public function getRouter ()
    {
        if ( ! isset($this->router)) {
            $this->router = new Router($this->config);
        }
        return $this->router;
    }
    public function uri ($uri, $query_params=array(), $fragment="")
    {
        return new Uri($uri, $query_params, $fragment, $this);
    }
}
