<?php
namespace R\Lib\Http;

class Router
{
    private $webroot;
    private $base_uri;
    private $route_dispatcher;
    public function __construct ($webroot, array $routes)
    {
        $this->webroot = $webroot;
        $this->routes = $routes;
        // RouteDispatcherの構築
        $route_collector = new \FastRoute\RouteCollector(
            new \FastRoute\RouteParser\Std,
            new \FastRoute\DataGenerator\GroupCountBased
        );
        foreach ((array)$this->routes as $route) {
            $method = $route["method"] ?: array("GET","POST");
            $pattern = $this->webroot->getBaseUri()->getPath().$route[1];
            $page_id = $route[0];
            $route_collector->addRoute($method, $pattern, $page_id);
        }
        $route_data = $route_collector->getData();
        $this->route_dispatcher = new \FastRoute\Dispatcher\GroupCountBased($route_data);
    }
    public function parseUri($uri)
    {
        // 相対解決用BaseUri
        $request_uri = $this->webroot->getBaseUri();
        $parsed = array();
        $parsed["page_action"] = new PageAction($uri);
        if (strlen($uri->getHost()) && $uri->getHost() !== $request_uri->getHost()) {
            return $parsed;
        }
        if (preg_match('!^'.preg_quote($request_uri->getPath(), '!').'(.*?)$!', $uri->getPath(), $match)) {
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
        }report("",$parsed);
        return $parsed;
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
    public function getUriByPageId ($page_id, $embed_params=array())
    {
        $route = $this->getRouteByPageId($page_id);
        if ($route) {
            $route_data = $route_collector->getData();
            report($route_data);
        }
    }
}
