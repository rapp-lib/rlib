<?php
namespace R\Lib\Http;

class Router
{
    private $config;
    private $route_collector;
    private $route_dispatcher;
    public function __construct (array $webroot_config)
    {
        $this->config = $webroot_config;
        // FastRoute
        $this->route_collector = new \FastRoute\RouteCollector();
        foreach ($this->config["routes"] as $route) {
            $method = $route["method"] ?: array("GET","POST");
            $pattern = $this->config["base_uri"].$route[1];
            $page_id = $route[0];
            $this->route_collector->addRoute($method, $pattern, $page_id);
        }
        $this->route_dispatcher = \FastRoute\simpleDispatcher($this->route_collector);
    }
    public function parseUri($uri)
    {
        $parsed = array();
        if (strlen($uri->getHost())) {
            return array();
        }
        if (preg_match('!^'.preg_quote($this->config["base_uri"], '!').'(.*?)$!', $uri->getPath(), $match)) {
            $parsed["page_path"] = $match[1];
        } else {
            return array();
        }
        $routed = $this->route_dispatcher->dispatch($method, $uri);
        if ($routed[0] === \FastRoute\Dispatcher::FOUND) {
            $parsed["page_id"] = $routed[1];
            $parsed["embed_params"] = $routed[2];
            $parsed["route"] = $this->getRouteByPageId($parsed["page_id"]);
            if ($parsed["embed_params"]) {
                $pattern = str_replace(array('[',']'),'',$parsed["route"]["pattern"]);
                $parsed["src_file"] = preg_replace('!\{([^:]+):\}!', '[$1]', $pattern);
            }
        }
        if ($parsed["page_path"] && ! $parsed["src_file"]) {
            $parsed["src_file"] = $parsed["page_path"];
        }
        if ($parsed["src_file"]) {
            $parsed["src_file"] = $this->config["src_dir"].$parsed["src_file"];
            if (preg_match('!/$!', $parsed["src_file"])) {
                $parsed["src_file"] .= 'index.html';
            } elseif ( ! preg_match('!\.\w+$!', $parsed["src_file"])) {
                $parsed["src_file"] .= '/index.html';
            }
        }
        return $parsed;
    }
    public function getUriByPageId ($page_id, $embed_params=array())
    {
        $route = $this->getRouteByPageId($page_id);
        if ($route) {
            $route_collector->getData();
            todo;
        }
    }
    public function getRouteByPageId ($page_id)
    {
        foreach ($this->config["routes"] as $route_info) {
            if ($route_info[0] === $page_id) {
                $route = $route_info[2] ?: array();
                $route["page_id"] = $route_info[0];
                $route["pattern"] = $route_info[1];
                return $route;
            }
        }
        return array();
    }
}
