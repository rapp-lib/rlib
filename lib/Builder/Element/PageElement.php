<?php
namespace R\Lib\Builder\Element;

class PageElement extends Element_Base
{
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getParent()->getAttr("type").".pages.".$this->getAttr("type");
    }
    public function getLabel ()
    {
        return $this->getParent()->getLabel();
    }
    public function getPath ()
    {
        return "/".$this->getName().".html";
    }
    public function getFullPage ()
    {
        return $this->getParent()->getParent()->getName().".".$this->getName();
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
        $pageset = $this->getParent();
        $controller = $pageset->getParent();
        $role = $this->getRole();
        return $this->getSchema()->fetch($this->getTemplateEntry(), array(
            "page"=>$this, "pageset"=>$pageset, "controller"=>$controller, "role"=>$role));
    }
    /**
     * Controller中でのメソッド宣言部分のPHPコードを取得
     */
    public function getMethodDecSource ()
    {
        return $this->getSchema()->fetch("frame.page_method_dec", array("page"=>$this));
    }
}
