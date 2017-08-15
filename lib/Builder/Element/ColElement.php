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
            return $this->getParent()->getEnum()->getEnumSetByName($this->getName());
        }
        return null;
    }
    /**
     * 表示HTMLソースの取得
     */
    public function getShowSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_show", array("col"=>$this, "o"=>$o));
    }
    /**
     * 入力HTMLソースの取得
     */
    public function getInputSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_input", array("col"=>$this, "o"=>$o));
    }
    /**
     * $form_entryの定義行の取得
     */
    public function getEntryFormFieldDefSource ($o=array())
    {
        $name = $o["name_parent"] ? $o["name_parent"].".".$this->getName() : $this->getName();
        $def = array();
        $def["label"] = $this->getLabel();
        if ($this->getAttr("type")=="file") {
            $def["storage"] = "public";
        }
        return '            '.$this->stringifyValue($name, $def).','."\n";

    }
    /**
     * $colsの定義行の取得
     */
    public function getColDefSource ($o=array())
    {
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        if ($this->getAttr("type")=="checklist" && $def["type"]=="text" && ! $def["format"]) {
            $def["format"] = "json";
        }
        return '        '.$this->stringifyValue($this->getName(), $def).','."\n";
    }
    /**
     * assoc関係にあるTableを取得
     */
    public function getAssocTable ()
    {
        $table_name = $this->getAttr("def.assoc.table");
        return $table_name ? $this->getSchema()->getTableByName($table_name) : null;
    }
    private function stringifyValue($k, $v)
    {
        if (is_array($v)) {
            foreach ($v as $k2=>$v2) {
                $v[$k2] = $this->stringifyValue($k2,$v2);
            }
            $v = 'array('.implode(', ',$v).')';
        } elseif (is_numeric($v)) {
        } elseif (is_string($v)) {
            $v = '"'.$v.'"';
        } elseif (is_null($v)) {
            $v = 'null';
        } elseif (is_bool($v)) {
            $v = $v ? 'true' : 'false';
        } else {
            $v = (string)$v;
        }
        return '"'.$k.'"=>'.$v;
    }
}
