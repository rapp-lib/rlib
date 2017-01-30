<?php
namespace R\Lib\Builder\Element;

class PageElement extends Element_Base
{
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getParent()->getAttr("type").".pages.".$this->getAttr("type");
    }
    public function getInnerSource ()
    {
        $pageset = $this->getParent();
        $controller = $pageset->getParent();
        $role = $this->getRole();
        return $this->getSchema()->fetch($this->getTemplateEntry(), array(
            "page"=>$this, "pageset"=>$pageset, "controller"=>$controller, "role"=>$role));
    }
    public function getLabel ()
    {
        return $this->getParent()->getLabel();
    }
    public function getPath ()
    {
        return "/".$this->getName().".html";
    }
    public function hasHtml ()
    {
        return $this->getSchema()->getConfig($this->getTemplateEntry().".template_file");
    }
}
