<?php
namespace R\Lib\Http;

class Router
{
    private $webroot;
    private $base_uri;
    private $route_dispatcher;
    public function __construct ($webroot, array $routes_config)
    {
        $this->webroot = $webroot;
        $this->routes = self::normalizeRoutesConfig($routes_config, $webroot->getBaseUri()->getPath(), array());
        // RouteDispatcherの構築
        $route_collector = new \FastRoute\RouteCollector(
            new \FastRoute\RouteParser\Std,
            new \FastRoute\DataGenerator\GroupCountBased
        );
        foreach ($this->routes as $route) {
            $route_collector->addRoute("ROUTE", $route["pattern"], $route["page_id"]);
        }
        $this->route_dispatcher = new \FastRoute\Dispatcher\GroupCountBased($route_collector->getData());
    }
    public function parseUri($uri)
    {
        // 相対解決用BaseUri
        $request_uri = $this->webroot->getBaseUri();
        $parsed = array();
        if (strlen($uri->getHost()) && $uri->getHost() !== $request_uri->getHost()) {
            return $parsed;
        }
        if (preg_match('!^'.preg_quote($request_uri->getPath(), '!').'(.*?)$!', $uri->getPath(), $match)) {
            $parsed["page_path"] = $match[1];
        } else {
            return array();
        }
        $request_path = preg_replace('!/index\.\w+$!', '/', $uri->getPath());
        $routed = $this->route_dispatcher->dispatch("ROUTE", $request_path);
        if ($routed[0] === \FastRoute\Dispatcher::FOUND) {
            $parsed["page_id"] = $routed[1];
            $parsed["embed_params"] = $routed[2];
            $parsed["route"] = $this->getRouteByPageId($parsed["page_id"]);
            if ($parsed["embed_params"] && ! $parsed["route"]["static_route"]) {
                $pattern = str_replace(array('[',']'),'',$parsed["route"]["pattern"]);
                $parsed["page_file"] = preg_replace('!\{([^:]+):[^\}]+\}!', '{$1}', $pattern);
            }
        }
        if ($parsed["page_path"] && ! $parsed["page_file"]) {
            $parsed["page_file"] = $parsed["page_path"];
        }
        if ($parsed["page_file"]) {
            $parsed["page_file"] = $this->webroot->getBaseDir().$parsed["page_file"];
            if (preg_match('!/$!', $parsed["page_file"])) {
                $parsed["page_file"] .= 'index.html';
            } elseif ( ! preg_match('!\.\w+$!', $parsed["page_file"])) {
                $parsed["page_file"] .= '/index.html';
            }
        }
        return $parsed;
    }
    public function getRouteByPageId ($page_id)
    {
        foreach ($this->routes as $route) {
            if ($route["page_id"] === $page_id) {
                return $route;
            }
        }
        return array();
    }
    public function buildUriStringByPageId ($page_id, $embed_params=array())
    {
        // RouteからPatternを取得
        $route = $this->getRouteByPageId($page_id);
        // embed_paramsを置き換える
        $page_path = $route["pattern"];
        $page_path = preg_replace_callback('!\{([^:]+)(?::([^\}]+))?\}!', function($match)use($embed_params){
            return isset($embed_params[$match[1]]) ? $embed_params[$match[1]] : "@@EMBED@@";
        }, $page_path);
        // [...]で囲まれた範囲で不完全な部分を排除
        while (preg_match('!\[[^\[\]]*?@@EMBED@@[^\[\]]*?\]!', $page_path)) {
            $page_path = preg_replace('!\[[^\[\]]*?@@EMBED@@[^\[\]]*?\]!', '', $page_path);
        }
        // [...]内が完全であればそのまま残す
        $page_path = preg_replace('!\[\[\]]!', '', $page_path);
        // [...]外の置き換え漏れは''で置き換える
        $page_path = preg_replace('!@@EMBED@@!', '', $page_path);
        // base_uriをつける
        return $this->buildUriStringByPagePath($page_path);
    }
    public function buildUriStringByPagePath ($page_path)
    {
        // base_uriをつけてUriにする
        return $this->webroot->getBaseUri().$page_path;
    }

// --

    private static function normalizeRoutesConfig ($grouped, $base_path="", $route_config=array())
    {
        $routes = array();
        foreach ($grouped as $row) {
            if (is_string($row[0])) {
                $route = array_merge((array)$route_config, (array)$row[2]);
                $route["page_id"] = $row[0];
                $route["pattern"] = $base_path.$row[1];
                $routes[] = $route;
            } elseif (is_array($row[0])) {
                foreach ((array)self::normalizeRoutesConfig($row[0], $row[1], $row[2]) as $append_route) {
                    $routes[] = $append_route;
                }
            }
        }
        return $routes;
    }
}
