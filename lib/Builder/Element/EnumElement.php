<?php
namespace R\Lib\Builder\Element;

class EnumElement extends Element_Base
{
    public function init ()
    {
        foreach ((array)$this->getAttr("enum_sets") as $enum_set_name => $enum_set_attrs) {
            $this->children["enum_set"] = new EnumSet($enum_set_name,$enum_set_attrs,null);
        }
    }
    public function getClassName ()
    {
        return str_camelize($this->getName())."Enum";
    }
    /**
     * @getter EnumSet
     */
    public function getEnumSets ()
    {
        return (array)$this->children["enum_set"];
    }
    public function getEnumSetByName ($name)
    {
        return $this->children["enum_set"][$name];
    }
}
