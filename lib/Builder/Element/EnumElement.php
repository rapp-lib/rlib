<?php
namespace R\Lib\Builder\Element;

class EnumElement extends Element_Base
{
    public function init ()
    {
        $enum_sets = (array)$this->getAttr("enum_sets");
        unset($this->attrs["enum_sets"]);
        foreach ($enum_sets as $enum_set_name => $enum_set_attrs) {
            $this->children["enum_set"][$enum_set_name] = new EnumSetElement($enum_set_name,$enum_set_attrs,$this);
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
