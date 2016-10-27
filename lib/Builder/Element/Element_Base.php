<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
abstract class Element_Base
{
    protected $name;
    protected $attrs;
    protected $parent;

    abstract protected function init ();

    public function __construct ($name="", $attrs=array(), $parent=null)
    {
        $this->name = $name;
        $this->attrs = $attrs;
        $this->parent = $parent;

        $this->init();
    }

    public function getName ()
    {
        return $this->name;
    }

    public function getAttr ($key=null)
    {
        if ( ! $key) {
            return $this->attrs;
        }
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
