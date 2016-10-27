<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ColElement extends Element_Base
{
    /**
     * @override
     */
    protected function init ()
    {
    }
    /**
     * $colsの定義行の取得
     */
    public function getColDef ()
    {
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        foreach ($def as $k => $v) {
            if (is_numeric($v)) {
                $v = $v;
            } elseif (is_string($v)) {
                $v = '"'.$v.'"';
            } elseif (is_null($v)) {
                $v = 'null';
            } elseif (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            } else {
                $v = (string)$v;
            }
            $def[$k] = '"'.$k.'"=>'.$v;
        }
        return '"'.$this->getName().'" => array('.implode(', ',$def).'),';
    }
}
