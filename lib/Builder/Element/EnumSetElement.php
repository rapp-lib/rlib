<?php
namespace R\Lib\Builder\Element;

class EnumSetElement extends Element_Base
{
    public function getFullName ()
    {
        return $this->getParent()->getName().".".$this->getName();
    }
    public function getCol ()
    {
        return $this->getParent()->getParent()->getColByName($this->getAttr("col_name"));
    }
}
