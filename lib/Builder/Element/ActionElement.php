<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ActionElement extends Element_Base
{
    /**
     * @override
     */
    protected function init ()
    {
    }
    /**
     * Pathを取得
     */
    public function getPath ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();

        $path = "/".str_replace('_','/',$controller_name);

        // index.static => /*
        if ($this->getParent()->getAttr("type")=="index" && $action_name=="static") {
            $path = preg_replace('!/[^\/]+$!','/*',$path);
        // index.index => /index.html
        } elseif ($this->getParent()->getAttr("type")=="index" && $action_name=="index") {
            $path = preg_replace('!/[^\/]+$!','/index.html',$path);
        } else {
            $path .= "/".$action_name.".html";
        }

        return $path;
    }
    /**
     * Pageを取得
     */
    public function getPage ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();
        return $controller_name.".".$action_name;
    }
}
