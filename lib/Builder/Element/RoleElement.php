<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class RoleElement extends Element_Base
{
    protected function init ()
    {
    }
    /**
     * クラス名を取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Role";
    }
    /**
     * ログイン後TOPとなるControllerの取得
     */
    public function getIndexController ()
    {
        foreach ($this->getAccessibleControllers() as $controller) {
            if ($controller->getAttr("type") == "index" && $controller->getAttr("priv_required")) {
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
        foreach ($this->getSchema()->getController() as $controller) {
            if ($controller->getAttr("access_as") == $this->getName()) {
                $controllers[] = $controller;
            }
        }
        return $controllers;
    }
}
