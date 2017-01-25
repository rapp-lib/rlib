<?php
namespace R\Lib\Route;

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
     *      "domain_name" => "www.example.com",
     *      "docroot_dir" => "/var/www/html",
     *      "webroot_url" => "/system",
     *      "directory_index" => "index.html",
     */
    private $config = array();
    /**
     *
     */
    public function __construct ($route_manager, $webroot_name)
    {
        $this->route_manager = $route_manager;
        $this->webroot_name = $webroot_name;
        $config = (array)app()->config("router.webroot.".$webroot_name.".config");
        $this->config = $config;
        $routing = (array)app()->config("router.webroot.".$webroot_name.".routing");
        $this->routing = array_dot($routing);
    }
    /**
     * @getter
     */
    public function getRouteManager ()
    {
        return $this->route_manager;
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
    public function setConfig ($config)
    {
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }
    }
    /**
     * 設定値の取得
     */
    public function getConfig ($key, $required=false)
    {
        if ($required && ! isset($this->config[$key])) {
            report_error("設定値が未設定です",array(
                "key" => $key,
                "webroot_name" => $this->webroot_name,
                "config" => $this->config,
            ));
        }
        return $this->config[$key];
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
    /**
     * Page→Pathの変換
     * ※embedは関与しない
     */
    public function pageToPath ($page)
    {
        return isset($this->routing[$page]) ? $this->routing[$page] : null;
    }
    /**
     * Path→Pageの変換
     */
    public function pathToPage ($path)
    {
        $page = array_search($path, $this->routing);
        return strlen($page) ? $page : null;
    }
    /**
     * URLからPathと、必要に応じて埋め込みパラメータを取得する
     * @return array array($path, $url_params, $path_matched)
     */
    public function parseUrl ($url)
    {
        $url_params = array();
        // QUERY_STRINGの分離
        if (preg_match('!^([^\?]+)\?(.*)$!', $url, $match)) {
            list(, $url, $query_string) =$match;
            parse_str($query_string, $url_params);
            //$params = array_replace_recursive($url_params, $params);
        }
        // webroot_urlを削る
        $webroot_url = $this->getConfig("webroot_url",true);
        // 変換できない領域のURLであればそのままあつかう
        if (strlen($webroot_url) && strpos($webroot_url, $url)!==0) {
            return array(null, $url_params);
        }
        $path_tmp = str_replace($webroot_url, "", $url);
        // 末尾が"/"であればdirectory_indexを追加する
        if (preg_match('!/$!',$path_tmp)) {
            $path_tmp .= "index.html";
        }
        // Route定義の確認
        if ($this->pathToPage($path_tmp)) {
            return array($path_tmp, $url_params);
        }
        // パターン一致Routeの確認
        foreach ($this->getPathPatterns() as $path => $pattern) {
            if (preg_match($pattern["regex"], $path_tmp, $match)) {
                // 埋め込みパラメータの抽出
                if ($pattern["value_names"]) {
                    foreach ($pattern["value_names"] as $i => $value_name) {
                        $url_params[$value_name] = $match[$i+1];
                    }
                }
                return array($path, $url_params, $path_tmp);
            }
        }
        // Route定義のないURL
        return array($path_tmp, $url_params);
    }
    /**
     * Pathに対応する正規表現パターンを長い順で返す
     * @return array array($path => array("regex" => $regex, "value_names" => array($i => $value_name)))
     */
    private function getPathPatterns ()
    {
        $patterns = array();
        foreach ($this->routing as $path) {
            // "[xxx]"を含むPath
            if(preg_match_all('!\[(.*?)\]!', $path, $matches)) {
                $regex = '!^'.preg_quote($path,'!').'$!';
                $value_names = array();
                foreach ($matches[1] as $i => $value_name) {
                    $value_names[$i] = $value_name;
                    $regex = str_replace('\['.$value_name.'\]', '([a-zA-Z0-9_]+?)', $regex);
                }
                $patterns[$path] = array("regex"=>$regex, "value_names"=>$value_names);
            // "/*"の後方一致パターンを含むPath
            } elseif (preg_match('!/\*$!', $path)) {
                $regex = '!^'.preg_quote(preg_replace('!\*$!','',$path),'!').'.*?'.'$!';
                $patterns[$path] = array("regex"=>$regex, "value_names"=>null);
            }
        }
        // Pathの長い順で整列
        uksort($patterns, function($a,$b) {
            return strlen($b)-strlen($a);
        });
        return $patterns;
    }
    /**
     * @deprecated
     */
    public function __report ()
    {
        return array(
            "webroot_name" => $this->webroot_name,
            "config" => $this->config,
        );
    }
}
