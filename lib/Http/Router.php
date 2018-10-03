<?php
namespace R\Lib\Http;

class Router
{
    private $webroot;
    private $route_dispatcher = null;
    private $routes = null;
    public function __construct ($webroot)
    {
        $this->webroot = $webroot;
    }
    /**
     * Routesの取得
     */
    public function getRoutes ()
    {
        if ( ! $this->routes) {
            // Routesの構築
            $routes = $this->webroot->getRoutesConfig();
            $base_uri_path = $this->webroot->getBaseUri()->getPath();
            $routes = self::flattenGrouped($routes, $base_uri_path, array());
            $this->routes = self::sortStaticRoute($routes);
        }
        return $this->routes;
    }
    /**
     * RouteDispatcherの取得
     */
    public function getDispatcher ()
    {
        if ( ! $this->route_dispatcher) {
            // RouteDispatcherの構築
            $route_collector = new \FastRoute\RouteCollector(
                new \FastRoute\RouteParser\Std,
                new \FastRoute\DataGenerator\GroupCountBased
            );
            foreach ($this->getRoutes() as $route) {
                $route_collector->addRoute("ROUTE", $route["pattern"], $route["page_id"]);
            }
            $this->route_dispatcher = new \FastRoute\Dispatcher\GroupCountBased($route_collector->getData());
        }
        return $this->route_dispatcher;
    }
    /**
     * PageIDからRouteを取得
     */
    public function getRouteByPageId ($page_id)
    {
        foreach ($this->getRoutes() as $route) {
            if ($route["page_id"] === $page_id) {
                return $route;
            }
        }
        return array();
    }
    /**
     * UriからRoutesを通して取得可能な情報を取得
     */
    public function parseUri($uri)
    {
        // 相対解決用BaseUri
        $base_uri = $this->webroot->getBaseUri();
        $parsed = array();
        if (strlen($uri->getHost()) && ! $this->webroot->inUri($uri)) {
            return $parsed;
        }
        if ($page_path = $this->requestPathToPagePath($uri->getPath())) {
            $parsed["page_path"] = urldecode($page_path);
        } else {
            return array();
        }
        $request_path = preg_replace('!/index\.\w+$!', '/', $uri->getPath());
        $routed = $this->getDispatcher()->dispatch("ROUTE", $request_path);
        if ($routed[0] === \FastRoute\Dispatcher::FOUND) {
            $parsed["page_id"] = $routed[1];
            $parsed["embed_params"] = array_map("urldecode", (array)$routed[2]);
            $parsed["route"] = $this->getRouteByPageId($parsed["page_id"]);
            if ($parsed["embed_params"] && ! $parsed["route"]["static_route"]) {
                $pattern = str_replace(array('[',']'),'',$parsed["route"]["pattern"]);
                $parsed["page_file"] = preg_replace('!\{([^:]+):[^\}]+\}!', '{$1}', $pattern);
                $parsed["page_file"] = $this->requestPathToPagePath($parsed["page_file"]);
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
    /**
     * GETパラメータからURL埋め込みパラメータを分離する
     */
    public function filterEmbedParamsFromQueryParams ($page_id, & $query_params)
    {
        // RouteからPatternを取得
        $route = $this->getRouteByPageId($page_id);
        // embed_paramsを置き換える
        $page_path = $route["pattern"];
        $embed_params = array();
        $page_path = preg_replace_callback('!\{([^:]+)(?::([^\}]+))?\}!', function($match)use( & $embed_params, & $query_params){
            $embed_params[$match[1]] = $query_params[$match[1]];
            unset($query_params[$match[1]]);
        }, $page_path);
        return $embed_params;
    }
    /**
     * PageIDからURL文字列を組み立てる
     */
    public function buildUriStringByPageId ($page_id, $embed_params=array())
    {
        // RouteからPatternを取得
        $route = $this->getRouteByPageId($page_id);
        // embed_paramsを置き換える
        $page_path = $this->requestPathToPagePath($route["pattern"]);
        if ( ! $page_path) return false;
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
        if ( ! strlen($page_path)) {
            report_warning("PageIDに対応するURIが構築できません", array(
                "page_id"=>$page_id,
            ));
        }
        // base_uriをつける
        return $this->buildUriStringByPagePath($page_path);
    }
    /**
     * PagePathからURL文字列を組み立てる
     */
    public function buildUriStringByPagePath ($page_path)
    {
        // base_uriをつけてUriにする
        return $this->webroot->getBaseUri().$page_path;
    }
    /**
     * BaseUriのついたPathからPagePathを取り出す
     */
    private function requestPathToPagePath($request_path)
    {
        $base_uri = $this->webroot->getBaseUri();
        return preg_match('!^'.preg_quote($base_uri->getPath(), '!').'(.*?)$!', $request_path, $_) ? $_[1] : false;
    }
    /**
     * グループ階層化された設定を平坦に変換する
     */
    private static function flattenGrouped ($grouped, $base_path="", $route_config=array())
    {
        $routes = array();
        foreach ($grouped as $row) {
            if (is_string($row[0])) {
                $route = (array)$route_config;
                foreach ((array)$row[2] as $k=>$v) \R\Lib\Util\Arr::array_add($route, $k, $v);
                $route["page_id"] = $row[0];
                $route["pattern"] = $base_path.$row[1];
                $routes[] = $route;
            } elseif (is_array($row[0])) {
                foreach ((array)self::flattenGrouped($row[0], $base_path.$row[1], $row[2]) as $append_route) {
                    $routes[] = $append_route;
                }
            }
        }
        return $routes;
    }
    /**
     * 曖昧なパターンマッチを後回しにする
     */
    private static function sortStaticRoute ($routes)
    {
        // static_route以外を優先で記載順
        $routes_sorted = array();
        foreach ($routes as $route) if ( ! $route["static_route"]) $routes_sorted[] = $route;
        // static_routeはパターン文字数の少ない順
        $routes_static = array();
        foreach ($routes as $route) if (   $route["static_route"]) $routes_static[] = $route;
        usort($routes_static, function($a, $b){
            $a_length = strlen(preg_replace('!\{.*?\}!','',$a["pattern"]));
            $b_length = strlen(preg_replace('!\{.*?\}!','',$b["pattern"]));
            if ($a_length == $b_length) return 0;
            return $a_length > $b_length ? +1 : -1;
        });
        foreach ($routes_static as $route) $routes_sorted[] = $route;
        return $routes_sorted;
    }
}
