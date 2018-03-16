<?php
namespace R\Lib\Builder\Element;

class PageElement extends Element_Base
{
    public function getPageset ()
    {
        return $this->getParent();
    }
    public function getController ()
    {
        return $this->getPageset()->getParent();
    }
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getParent()->getAttr("type").".pages.".$this->getAttr("type");
    }
    public function getSkelConfig ($key)
    {
        return $this->getSchema()->getConfig($this->getTemplateEntry().".".$key);
    }
    public function getTitle ()
    {
        if ($title = $this->getAttr("title")) return $title;
        if ($label = $this->getAttr("label")) return $label;
        $title = $this->getParent()->getTitle();
        if ($this->getParent()->getIndexPage() !== $this) {
            if ($label = $this->getSkelConfig("label")) $title .= " ".$label;
        }
        return $title;
    }
    public function getPath ()
    {
        $path = "/".str_replace('_','/',$this->getController()->getName())."/".$this->getName().".html";
        if (preg_match('!/index/index\.html$!',$path)) {
            $path = preg_replace('!/index/index\.html$!','/',$path);
        } elseif (preg_match('!/index/index_static\.html$!',$path)) {
            $path = preg_replace('!/index/index_static\.html$!','/{FILE:.+}',$path);
        } elseif ($this->getController()->getIndexPage()===$this) {
            $path = preg_replace('!/[^/\.]+\.html$!','/',$path);
        }
        return $path;
    }
    public function getPathFile ()
    {
        $path = $this->getPath();
        $path = preg_replace('!/$!', '/index.html', $path);
        $path = str_replace(array('[',']'),'',$path);
        $path = preg_replace('!\{([^:]+):\}!', '\{$1\}', $path);
        return $path;
    }
    public function getPathPattern ()
    {
        $path = $this->getPath();
        return $path;
    }
    public function getFullPage ($page=null)
    {
        if (isset($page) && $page->getParent()==$this->getParent()) {
            return $this->getLocalPage();
        }
        return $this->getController()->getName().".".$this->getName();
    }
    /**
     * @deprecated getFullPage
     */
    public function getLocalPage ()
    {
        return ".".$this->getName();
    }
    public function hasHtml ()
    {
        if (($has_html = $this->getAttr("has_html")) !== null) return $has_html;
        return $this->getSchema()->getConfig($this->getTemplateEntry().".template_file");
    }
    /**
     * Page固有のHtmlコードを取得、frame内で呼び出す
     */
    public function getInnerSource ()
    {
        $controller = $this->getController();
        $role = $controller->getRole();
        $table = $controller->getTable();
        return $this->getSchema()->fetch($this->getTemplateEntry(), array(
            "page"=>$this, "pageset"=>$this->getParent(),
            "controller"=>$controller, "role"=>$role, "table"=>$table));
    }
    /**
     * Controller中でのメソッド宣言部分のPHPコードを取得
     */
    public function getMethodDecSource ()
    {
        return $this->getSchema()->fetch("parts.page_method_dec", array("page"=>$this));
    }
    /**
     * Route設定部分のPHPコードを取得
     */
    public function getRouteSource ()
    {
        $routes_dec = array();
        //TODO: ページタイトルを設定してパンくずを生成できるようにする
        //$routes_dec[] = '"title"=>"'.$page->getTitle().'"';
        if ($this->getController()->getType()=="index" && $this->getAttr("type")=="static") {
            $routes_dec[] = '"static_route"=>true';
        }
        if ($this->getController()->getRole()->getName()!="guest" && ! $this->getController()->getPrivRequired()) {
            $routes_dec[] = '"auth.priv_req"=>false';
        }
        if ($this->getPageset()->getType()=="delete" && $this->getAttr("is_index_page")) {
            $routes_dec[] = '"csrf_check"=>true';
        }
        return 'array("'.$this->getFullPage().'", "'.$this->getPathPattern().'"'
            .($routes_dec ? ', array('.implode(', ',$routes_dec).')' : '').'),';
    }
    /**
     * リンク先Pageへのリンク記述コードを取得
     */
    public function getLinkSource ($type, $from_pageset=null, $o=array())
    {
        $params = $o["params"];
        $source = "";
        if ($type=="redirect") {
            $source .= 'return $this->redirect("id://'.$this->getFullPage($from_pageset->getIndexPage()).'"';
            if ($params) {
                $source .= ', array(';
                foreach ($params as $k=>$v) $params[$k] = '"'.$k.'"=>'.$v;
                $source .= implode(', ', $params);
                $source .= ')';
            }
            $source .= ');';
        } else {
            $source .= '{{"'.$this->getFullPage($from_pageset->getIndexPage()).'"|page_to_url';
            if ($params) {
                $source .= ':[';
                foreach ($params as $k=>$v) $params[$k] = '"'.$k.'"=>'.$v;
                $source .= implode(', ', $params);
                $source .= ']';
            }
            $source .= '}}';
        }
        return $source;
    }
}
