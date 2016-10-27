<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class EnumElement extends Element_Base
{
    /**
     * @override
     */
    protected function init ()
    {
    }
    /**
     * クラス名の取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Enum";
    }
    /**
     * EnumSet名の取得
     */
    public function getSetNames ()
    {
        return (array)$this->getAttr("set_names");
    }
}
