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
        $var_name = $o["var_name"] ?: '$t';
        $name = $o["name_parent"] ? $o["name_parent"].".".$this->getName() : $this->getName();
        if ($this->getAttr("type")=="mi") {
            //TODO: mi input
            return;
        }
        $html = '{{'.$var_name.'.'.$name;
        if ($this->getAttr("type")=="date") {
            $html .='|date:"Y/m/d"';
        }
        if ($this->getAttr("type")=="datetime") {
            $html .='|date:"Y/m/d H:i"';
        }
        if ($enum_set = $this->getEnumSet()) {
            $html .='|enum_value:"'.$enum_set->getFullName().'"';
        }
        $html .= '}}';
        if ($this->getAttr("type")=="file") {
            $html .= '{{if '.$var_name.'.'.$name.'}} <a href="{{'.
                $var_name.'.'.$name.'}}" target="_blank">ファイル</a>{{/if}}';
        }
        return $html;
    }
    /**
     * 入力HTMLソースの取得
     */
    public function getInputSource ($o=array())
    {
        $var_name = $o["var_name"] ?: '$forms.entry';
        $name = $o["name_parent"] ? $o["name_parent"]."[".$this->getName()."]" : $this->getName();
        if ($this->getAttr("type")=="mi") {
            //TODO: mi input
            return;
        }
        $html = '{{input name="'.$name.'" type="'.$this->getAttr("type").'"';
        if ($enum_set = $this->getEnumSet()) {
            $html .=' enum="'.$enum_set->getFullName().'"';
        }
        if ($this->getAttr("type")=="password") {
            $html .=' autocomplete="new-password"';
        }
        $html .= '}}';
        if ($this->getAttr("type")=="file") {
            $html .= '{{if '.$var_name.'.'.$this->getName().'}}<span> <a href="{{'
                .$var_name.'.'.$this->getName().'}}" target="_blank" class="uploaded">ファイル</a>'
                .' <a href="javascript:void(0);" onclick="$(this).parent().parent().find(\'input.uploaded\').val(\'\');'
                .'$(this).parent().hide();">削除</a></span>{{/if}}';
        }
        return $html;
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
