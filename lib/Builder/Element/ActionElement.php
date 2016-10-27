<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ActionElement extends Element_Base
{
    protected function init ()
    {
    }

    public function getPath ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();

        $path = "/".str_replace('_','/',$controller_name);

        // act_index単一であれば階層を上げる
        if (count($this->getParent()->getAction())==1 && $action_name=="index") {
            $path .= ".html";
        } else {
            $path .= "/".$action_name.".html";
        }

        return $path;
    }

    public function getPage ()
    {
        $controller_name = $this->getParent()->getName();
        $action_name = $this->getName();
        return $controller_name.".".$action_name;
    }
}
