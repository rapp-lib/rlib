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
        return $this->getController()->getLabel();
    }
    public function getLabel ()
    {
        return $this->getParent()->getLabel();
    }
    public function getPath ()
    {
        $path = "/".str_replace('_','/',$this->getController()->getName())."/".$this->getName().".html";
        if (preg_match('!/index/index\.html$!',$path)) {
            $path = preg_replace('!/index/index\.html$!','/index.html',$path);
        } elseif (preg_match('!/index/static\.html$!',$path)) {
            $path = preg_replace('!/index/static\.html$!','/*',$path);
        }
        return $path;
    }
    public function getFullPage ($page=null)
    {
        if (isset($page) && $page->getParent()==$this) {
            return $this->getLocalPage();
        }
        return $this->getController()->getName().".".$this->getName();
    }
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
}
