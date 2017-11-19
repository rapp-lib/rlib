<?php
namespace R\Lib\Builder\Element;

class PageElement extends Element_Base
{
    public function getController ()
    {
        return $this->getParent()->getParent();
    }
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getParent()->getAttr("type").".pages.".$this->getAttr("type");
    }
    public function getTitle ()
    {
        //TODO:固有の名称を生成すべき
        return $this->getController()->getLabel();
    }
    public function getLabel ()
    {
        //TODO:固有の名称を生成すべき
        return $this->getParent()->getLabel();
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
        return 'array("'.$this->getFullPage().'", "'.$this->getPathPattern().'"'
            .($routes_dec ? ', array('.implode(', ',$routes_dec).')' : '').'),';
    }
}
