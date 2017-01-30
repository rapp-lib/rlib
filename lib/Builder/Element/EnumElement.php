<?php
namespace R\Lib\Builder\Element;

class EnumElement extends Element_Base
{
    public function getClassName ()
    {
        return str_camelize($this->getName())."Enum";
    }
    public function getSetNames ()
    {
        return (array)$this->getAttr("set_names");
    }
}
