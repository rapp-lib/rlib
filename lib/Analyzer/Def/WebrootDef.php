<?php
namespace R\Lib\Analyzer\Def;

class WebrootDef extends Def_Base
{
    public function getEntity()
    {
        return app()->http->webroot($this->getName());
    }
    public function getBaseDir()
    {
        $value = app()->config($key = "http.webroots.".$this->getName().".base_dir");
        if ( ! isset($value)) report_error("configが必要です", array("key"=>$key));
        return $value;
    }
    public function getBaseUri()
    {
        $value = app()->config($key = "http.webroots.".$this->getName().".base_uri");
        if ( ! isset($value)) report_error("configが必要です", array("key"=>$key));
        return $value;
    }

// -- route

    public function getRoute($name)
    {
        if ( ! $this->children["routes"][$name]) {
            $this->children["routes"][$name] = new RouteDef($this, $name);
        }
        return $this->children["routes"][$name];
    }
    public function getRoutes()
    {
        $routes_config = $this->getEntity()->getRouter()->getRoutes();
        foreach ($routes_config as $route_config) $this->getRoute($route_config["page_id"]);
        return $this->children["routes"];
    }

// -- html

    public function getHtml($name)
    {
        $base_dir = $this->getBaseDir();
        if ( ! preg_match('!\.html$!', $name) || ! is_file($base_dir.$name)) return null;
        if ( ! $this->children["htmls"][$name]) {
            $this->children["htmls"][$name] = new RouteDef($this, $name);
        }
        return $this->children["htmls"][$name];
    }
    public function getHtmls()
    {
        // webroot_dir配下のHTMLファイル全て
        $files = NameResolver::scanDir($this->getBaseDir());
        foreach ($files as $file) $this->getHtmlByFileName($file);
        return $this->children["htmls"];
    }
    public function getHtmlByFileName($file_name)
    {
        if (preg_match('!^'.preg_quote($this->getBaseDir(),"!").'/(.+)$!', $file_name, $_)) {
            return $this->getHtml($_[1]);
        }
    }
}
class RouteDef extends Def_Base
{
    public function getPageId()
    {
        return $this->getName();
    }
    public function getPagePath()
    {
        return $this->getUri()->getPagePath();
    }
    public function getRole()
    {
        return $this->getSchema()->getRole($this->getUri()->getRole());
    }
    public function getPrivReq()
    {
        return $this->getUri()->getPrivReq();
    }
    public function getUri()
    {
        return $this->getParent()->getEntity()->uri("id://".$this->getPageId());
    }
    public function getHtml()
    {
        $file = $this->getUri()->getPageFile();
        if ( ! is_file($file)) return null;
        return $this->getParent()->getHtmlByFileName($file);
    }
    public function getHtmlAll()
    {
        //TODO: HTMLを全探索してマッチするHTMLを全て取得する
        return array();
    }
}
class HtmlDef extends Def_Base
{
    public function getFileName()
    {
        return $this->getParent()->getBaseDir().$this->getName();
    }
    public function getTitle()
    {
        $src = file_get_contents($this->getFileName());
        if (preg_match('!^\{\{assign\s+"page_title"\s+"(.*)"\s*\}\}!', $src, $_)) {
            return $_[1];
        }
    }
    public function getRoute()
    {
        if (preg_match('!\{!', $this->getName())) {
            foreach ($this->getParent()->getRoutes() as $route) {
                $path = $route->getPagePath();
                $path = preg_replace('!\{([^:\}]+):[^:\}]+\})!', '{$1}', $path);
                if ($path===$this->getName()) return $route;
            }
        } else {
            $uri = $this->getParent()->getBaseUri().$this->getName();
            $uri = $this->getParent()->getEntity()->uri($uri);
            return $this->getParent()->getRoute($uri->getPageId());
        }
    }
}
