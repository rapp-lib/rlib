<?php
namespace R\Lib\Builder\Element;

class RoleElement extends Element_Base
{
    public function getClassName ()
    {
        return str_camelize($this->getName())."Role";
    }
    public function getRoleControllerClassName ()
    {
        return "Controller_".str_camelize($this->getName());
    }
    public function getHeaderPath ()
    {
        return "/include/".$this->getName()."_header.html";
    }
    public function getFooterPath ()
    {
        return "/include/".$this->getName()."_footer.html";
    }
    /**
     * ログイン後TOPとなるControllerの取得
     */
    public function getIndexController ()
    {
        foreach ($this->getAccessibleControllers() as $controller) {
            if ($controller->getAttr("type") == "index") {
                return $controller;
            }
        }
        return null;
    }
    /**
     * ログインControllerの取得
     */
    public function getLoginController ()
    {
        foreach ($this->getAccessibleControllers() as $controller) {
            if ($controller->getAttr("type") == "login") {
                return $controller;
            }
        }
        return null;
    }
    /**
     * アクセス可能なControllerを取得する
     */
    public function getAccessibleControllers ()
    {
        $controllers = array();
        foreach ($this->getSchema()->getControllers() as $controller) {
            if ($controller->getRole()->getName() == $this->getName()) {
                $controllers[] = $controller;
            }
        }
        return $controllers;
    }
}
