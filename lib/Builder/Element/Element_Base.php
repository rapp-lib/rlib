<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class Element_Base
{
    protected $name;
    protected $attrs;
    protected $parent;
    protected $children = array();
    public function __construct ($name="", $attrs=array(), $parent=null)
    {
        $this->name = $name;
        $this->attrs = $attrs;
        $this->parent = $parent;
        $this->init();
    }
    protected function init ()
    {
        //
    }
    public function getName ()
    {
        return $this->name;
    }
    public function getAttr ($key)
    {
        return $this->attrs[$key];
    }
    public function getParent ()
    {
        return $this->parent;
    }
    public function getSchema ()
    {
        if ( ! $this->parent) {
            return $this;
        } else {
            return $this->getParent()->getSchema();
        }
    }
}
