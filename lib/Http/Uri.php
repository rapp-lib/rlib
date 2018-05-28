<?php
namespace R\Lib\Http;
use Psr\Http\Message\UriInterface;

class Uri extends \Zend\Diactoros\Uri
{
    protected $webroot;
    protected $parsed = null;
    protected $page_controller = null;
    protected $page_auth = null;
    public function __construct ($webroot, $uri, $query_params=array(), $fragment="")
    {
        // Webrootの設定
        $this->webroot = $webroot;
        // page_idを元に初期化
        if (is_array($uri) && isset($uri["page_id"])) {
            if ( ! isset($uri["embed_params"])) {
                $uri["embed_params"] = $this->webroot->getRouter()->filterEmbedParamsFromQueryParams($uri["page_id"], $query_params);
            }
            $uri = $this->webroot->getRouter()->buildUriStringByPageId($uri["page_id"], $uri["embed_params"]);
        } elseif (is_string($uri) && preg_match('!^id://([^\?]+)$!', $uri, $match)) {
            $page_id = $match[1];
            $embed_params = $this->webroot->getRouter()->filterEmbedParamsFromQueryParams($page_id, $query_params);
            $uri = $this->webroot->getRouter()->buildUriStringByPageId($page_id, $embed_params);
        // page_pathを元に初期化
        } elseif (is_array($uri) && isset($uri["page_path"])) {
            $uri = $this->webroot->getRouter()->buildUriStringByPagePath($uri["page_path"]);
        } elseif (is_string($uri) && preg_match('!^path:///?(.*)$!', $uri, $match)) {
            $uri = $this->webroot->getRouter()->buildUriStringByPagePath("/".$match[1]);
        // UriInterfaceをもとに初期化
        } elseif ($uri instanceof Uri) {
            $uri = $uri->__toString();
        } elseif ($uri instanceof UriInterface) {
            $uri = $uri->__toString();
        }
        $uri = self::mergeQueryParams($uri, $query_params, $fragment);
        parent::__construct($uri);
    }

// --

    public function withoutAuthority()
    {
        $full_uri = parent::__toString();
        $uri = preg_replace('!^(https?:)?//[^/]+!', "", $full_uri);
        return new Uri($this->webroot,$uri);
    }
    public function withoutAuthorityInWebroot()
    {
        $full_uri = parent::__toString();
        if (preg_match('!^'.preg_quote($this->webroot->getBaseUri(),"!").'!', $full_uri)) {
            return $this->withoutAuthority();
        }
        return $this;
    }
    public function withToken()
    {
        if ($this->getRouteParam("csrf_check")===true) {
            return new Uri($this->webroot,$this,array(
                app()->security->getCsrfTokenName()=>app()->security->getCsrfToken()));
        }
        return $this;
    }

// -- uri解析結果取得

    public function getPageId()
    {
        $this->initParsed();
        return $this->parsed["page_id"];
    }
    public function getEmbedParam($key)
    {
        $this->initParsed();
        return $this->parsed["embed_params"][$key];
    }
    public function getEmbedParams()
    {
        $this->initParsed();
        return $this->parsed["embed_params"];
    }
    public function getPagePath()
    {
        $this->initParsed();
        return $this->parsed["page_path"];
    }
    public function getPageFile()
    {
        $this->initParsed();
        return $this->parsed["page_file"];
    }
    public function getRoute()
    {
        $this->initParsed();
        return $this->parsed["route"];
    }
    public function getRouteParam($key)
    {
        $this->initParsed();
        return $this->parsed["route"][$key];
    }
    private function initParsed()
    {
        if ( ! isset($this->parsed)) {
            $this->parsed = $this->webroot->getRouter()->parseUri($this);
        }
    }

// --

    public function getWebroot()
    {
        return $this->webroot;
    }
    public function getPageController()
    {
        if ( ! $this->page_controller) {
            $page_id = $this->getPageId();
            if ( ! $page_id) {
                report_error("URLに対応するPageIDがありません", array("uri"=>$this));
            }
            list($controller_name,) = explode('.', $page_id, 2);
            $controller_class = $this->webroot->getControllerClass($controller_name);
            if ( ! class_exists($controller_class)) {
                report_error("PageIDに対応するControllerがありません", array("page_id"=>$page_id));
            }
            $this->page_controller = new $controller_class($this);
        }
        return $this->page_controller;
    }
    public function getPageView ()
    {
        $route = $this->getRoute();
        return app()->view($route["view"] ?: "default");
    }
    public function getPageAuth()
    {
        if ( ! $this->page_auth) {
            $this->page_auth = new PageAuth($this);
        }
        return $this->page_auth;
    }
    public function getRelativeUri($uri, $query_params=array(), $fragment="")
    {
        // 相対page_idの解決
        if (is_string($uri) && preg_match('!^id://([^\?]+)$!', $uri, $match)) {
            $uri = array("page_id"=>$match[1]);
        }
        if (is_array($uri) && preg_match('!^\.([^\?\.]+)?$!', $uri["page_id"], $match)) {
            list($c, $a) = explode(".", $this->getPageId(), 2);
            $uri["page_id"] = $c.".".($match[1] ?: $a);
        }
        return new Uri($this->webroot, $uri, $query_params, $fragment);
    }

// -- static実装

    public static function mergeQueryParams($uri, $query_params=array(), $fragment="")
    {
        $uri = new \Zend\Diactoros\Uri("".$uri);
        parse_str($uri->getQuery(), $uri_query_params);
        $query_params = array_merge((array)$uri_query_params, (array)$query_params);
        self::normalizeQueryParamRecursive($query_params);
        $fragment = strlen($fragment) ? $fragment : $uri->getFragment();
        return self::buildUriString($uri->getScheme(), $uri->getAuthority(), $uri->getPath(),
            http_build_query($query_params), $fragment);
    }
    private static function normalizeQueryParamRecursive( & $arr)
    {
        foreach ($arr as & $v) if (is_array($v)) self::normalizeQueryParamRecursive($v);
        ksort($arr);
    }
    public static function buildUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';
        if ( ! empty($scheme)) $uri .= sprintf('%s://', $scheme);
        if ( ! empty($authority)) $uri .= $authority;
        if ($path) {
            if (empty($path) || '/' !== substr($path, 0, 1)) $path = '/' . $path;
            $uri .= $path;
        }
        if ($query) $uri .= sprintf('?%s', $query);
        if ($fragment) $uri .= sprintf('#%s', $fragment);
        return $uri;
    }

// --

    public function __report()
    {
        return array(
            "uri_string"=>"".$this,
            "parsed"=>$this->parsed,
            "webroot"=>$this->webroot,
        );
    }
}
