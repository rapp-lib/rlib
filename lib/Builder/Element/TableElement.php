<?php
namespace R\Lib\Builder\Element;

class TableElement extends Element_Base
{
    protected function init ()
    {
        // Col登録
        $cols = (array)$this->getAttr("cols");
        unset($this->attrs["cols"]);
        $enum_sets = array();
        foreach ($cols as $col_name => $col_attrs) {
            if (in_array($col_attrs["type"],array("select","radioselect","checklist"))) {
                $enum_sets[$col_name] = array("col_name"=>$col_name);
                $col_attrs["enum_set_name"] = $this->getName().".".$col_name;
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
    /**
     * テーブルクラス名
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Table";
    }
    /**
     * 定義上のテーブル名
     */
    public function getDefName ()
    {
        return $this->attrs["def"]["table_name"] ?: $this->getName();
    }
    /**
     * @getter children.col
     */
    public function getCols ()
    {
        return (array)$this->children["col"];
    }
    /**
     * @getter Col
     */
    public function getColByName ($col_name)
    {
        return $this->children["col"][$col_name];
    }
    public function getColsByAttr ($attr, $attr_value=true)
    {
        $cols = array();
        foreach ($this->getCols() as $col) {
            if ($value = $col->getAttr($attr)) {
                if ($attr_value===true || $value==$attr_value) $cols[] = $col;
            }
        }
        return $cols;
    }
    public function getColByAttr ($attr, $attr_value=true)
    {
        $cols = $this->getColsByAttr($attr, $attr_value);
        return $cols ? $cols[0] : null;
    }
    public function getIdCol ()
    {
        $id_col = $this->getColByAttr("def.id");
        if ( ! $id_col) report_error("TableにIDカラムがありません",array("table"=>$this));
        return $id_col;
    }
    public function getInputCols ()
    {
        return $this->getColsByAttr("type");
    }
    public function getOrdCol ()
    {
        return $this->getColByAttr("def.ord");
    }
    public function getLabelCol ()
    {
        return $this->getColByAttr("type", "text") ?: $this->getIdCol();
    }
    public function getIndexes ()
    {
        $indexes = array();
        foreach ($this->getCols() as $col) {
            if ($index_name = $col->getAttr("index")) {
                if ( ! isset($indexes[$index_name])) {
                    $indexes[$index_name] = array("name"=>$index_name, "cols"=>array());
                }
                $indexes[$index_name]["cols"][] = $col->getName();
            }
        }
        return $indexes;
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
