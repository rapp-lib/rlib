<?php
namespace R\Lib\Builder\Element;

class PagesetElement extends Element_Base
{
    public function init ()
    {
        // Page登録
        $page_configs = (array)$this->getSchema()->getConfig($this->getTemplateEntry().".pages");
        foreach ($page_configs as $page_type => $page_config) {
            $page_name = $this->getParent()->getName().".".$page_type;
            $page_attrs = array("type"=>$page_type);
            $this->children["page"][$page_name] = new PageElement($page_name, $page_attrs, $this);
        }
    }
    public function getTemplateEntry ()
    {
        return "pageset.".$this->getAttr("type").".controller";
    }
    public function getLabel ()
    {
        return $this->getParent()->getLabel();
    }
    /**
     * @getter Pages
     */
    public function getPages ()
    {
        return (array)$this->children["page"];
    }
    public function getPage ($name)
    {
        return $this->children["page"][$name];
    }
    /**
     * ControllerClass中のPHPコードを取得
     */
    public function getControllerSource ()
    {
        $controller = $this->getParent();
        $role = $this->getRole();
        return $this->getSchema()->fetch($this->getTemplateEntry(), array(
            "pageset"=>$this, "controller"=>$controller, "role"=>$role));
    }
}
