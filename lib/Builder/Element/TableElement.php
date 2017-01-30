<?php
namespace R\Lib\Builder\Element;

class TableElement extends Element_Base
{
    protected function init ()
    {
        // Col登録
        $cols = (array)$this->getAttr("cols_all");
        $enum_set_names = array();
        foreach ($cols as $col_name => $col_attrs) {
            $this->children["cols"][$col_name] = new ColElement($col_name, $col_attrs, $this);
            if (in_array($col_attrs["type"],array("select","radioselect","checklist"))) {
                $enum_set_names[] = $col_name;
            }
        }
        // Enum登録
        if ($enum_set_names) {
            $enum_attrs = array(
                "set_names" => $enum_set_names,
            );
            $this->children["enum"] = new EnumElement($this->getName(), $enum_attrs, $this);
        }
    }
    public function getClassName ()
    {
        return str_camelize($this->getName())."Table";
    }
    /**
     * @getter Cols
     */
    public function getCol ($name)
    {
        return $this->children["cols"][$name];
    }
    public function getCols ()
    {
        return $this->children["cols"];
    }
    /**
     * @getter
     */
    public function getEnum ()
    {
        return $this->children["enum"];
    }
    /**
     * Tableクラスの定義があるかどうか
     */
    public function hasDef ()
    {
        return ! $this->getAttr("nodef");
    }
}
