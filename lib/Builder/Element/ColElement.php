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
     * 入力HTMLソースの取得
     */
    public function getInputSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_input", array("col"=>$this, "o"=>$o));
    }
    /**
     * 表示HTMLソースの取得
     */
    public function getShowSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_show", array("col"=>$this, "o"=>$o));
    }
    /**
     * メール表示用PHPソースの取得
     */
    public function getMailSource ($o=array())
    {
        return $this->getSchema()->fetch("parts.col_mail", array("col"=>$this, "o"=>$o));
    }
    /**
     * $form_entryの定義行の取得
     */
    public function getEntryFormFieldDefSource ($o=array())
    {
        $name = $o["name_parent"] ? $o["name_parent"].".".$this->getName() : $this->getName();
        $def = array();
        $def["label"] = $this->getAttr("label");
        if ( ! $this->hasColDef()) $def["col"] = false;
        if ($this->getAttr("type")=="file") $def["storage"] = "public";
        return '            '.$this->stringifyValue($name, $def).','."\n";
    }
    /**
     * $colsの定義行の取得
     */
    public function getColDefSource ($o=array())
    {
        if ( ! $this->hasColDef()) return;
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        if ($this->getAttr("type")=="checklist" && $def["type"]=="text" && ! $def["format"]) {
            $def["format"] = "json";
        }
        return '        '.$this->stringifyValue($this->getName(), $def).','."\n";
    }
    /**
     * form.field_def中でのrule定義行の取得
     */
    public function getRuleDefSource ($o=array())
    {
        $page = $o["page"];
        $name = $this->getName();
        if ($o["name_parent"]) $name = $o["name_parent"].".".$name;

        $rules = array();
        foreach ((array)$this->getAttr("rules") as $type=>$params) {
            if ($type==="required" && $params===true) $rules[] = $name;
            else $rules[] = array_merge(array($name, $type), (array)$params);
        }
        // Enumの指定がある場合、正当性を検証するRuleを追加
        if ($enum_set = $this->getEnumSet()) {
            $rules[] = array($name, "enum", "enum"=>$enum_set->getFullName());
        }
        $source = "";
        // Ruleの値を配列コードとして出力
        foreach ($rules as $rule) $source .= '            '.$this->stringifyValue(0, $rule).','."\n";
        // assoc配下のRuleも出力
        if ($this->getAttr("type")==="assoc") {
            foreach ($this->getAssocTable()->getInputCols() as $assoc_col) {
                $source .= $assoc_col->getRuleDefSource(array("page"=>$page, "name_parent"=>$name.".*"));
            }
        }
        return $source;
    }
    /**
     * assoc関係にあるTableを取得
     */
    public function getAssocTable ()
    {
        $table_name = $this->getAttr("def.assoc.table");
        return $table_name ? $this->getSchema()->getTableByName($table_name) : null;
    }
    /**
     * Table上にColを持つかどうか、Colがない＝Form上のFieldのみ
     */
    public function hasColDef ()
    {
        return ! $this->getAttr("nodef");
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
        return (is_numeric($k) ? "" : '"'.$k.'"=>').$v;
    }
}
