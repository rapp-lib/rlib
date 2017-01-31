<?php
namespace R\Lib\Builder\Element;

class TableElement extends Element_Base
{
    protected function init ()
    {
        // Col登録
        $cols = (array)$this->getAttr("cols_all");
        $enum_sets = array();
        foreach ($cols as $col_name => $col_attrs) {
            if (in_array($col_attrs["type"],array("select","radioselect","checklist"))) {
                $enum_set_name = $this->getName().".".$col_name;
                $enum_sets[$enum_set_name] = array("col_name"=>$col_name);
                $col_attrs["enum_set_name"] = $enum_set_name;
            }
            $this->children["col"][$col_name] = new ColElement($col_name, $col_attrs, $this);
        }
        // Enum登録
        if ($enum_sets) {
            $enum_attrs = array(
                "enum_sets" => $enum_sets,
            );
            $this->children["enum"][0] = new EnumElement($this->getName(), $enum_attrs, $this);
        }
    }
    public function getClassName ()
    {
        return str_camelize($this->getName())."Table";
    }
    /**
     * IDのColを取得
     */
    public function getIdCol ()
    {
        foreach ($this->getCols() as $col) {
            if ($col->getAttr("def.id")) {
                return $col;
            }
        }
        return null;
    }
    /**
     * @getter children.col
     */
    public function getCols ()
    {
        return (array)$this->children["col"];
    }
    /**
     * @getter children.enum
     */
    public function getEnum ()
    {
        return $this->children["enum"][0];
    }
    /**
     * Tableクラスの定義があるかどうか
     */
    public function hasDef ()
    {
        return ! $this->getAttr("nodef");
    }
}
