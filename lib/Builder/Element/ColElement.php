<?php
namespace R\Lib\Builder\Element;

class ColElement extends Element_Base
{
    public function getLabel ()
    {
        return $this->getAttr("label");
    }
    /**
     * @getter EnumSet
     */
    public function getEnumSet ()
    {
        if ($enum_set_name = $this->getAttr("enum_set_name")) {
            return $this->getParent()->getEnum()->getEnumSetByName($enum_set_name);
        }
        return null;
    }
    /**
     * 表示HTMLソースの取得
     */
    public function getShowSource ($var_name)
    {
        return '{{'.$var_name.'.'.$this->getName().'}}';
    }
    /**
     * 入力HTMLソースの取得
     */
    public function getInputSource ()
    {
        return '{{input type="'.$this->getAttr("type").'" name="'.$this->getName().'"}}';
    }
    /**
     * $colsの定義行の取得
     */
    public function getColDef ()
    {
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        if ($this->getAttr("type")=="checklist" && ! $def["format"]) {
            $def["format"] = "json";
        }
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
