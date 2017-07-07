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
    public function getShowSource ($var_name='$t')
    {
        if ($this->getAttr("type")=="file") {
            $html = '{{if '.$var_name.'.'.$this->getName().'}}<a href="{{'.
                $var_name.'.'.$this->getName().'}}" target="_blank">ファイル</a>{{/if}}';
        } else {
            $html = '{{'.$var_name.'.'.$this->getName();
            if ($this->getAttr("type")=="date") {
                $html .='|date:"Y/m/d"';
            }
            if ($enum_set = $this->getEnumSet()) {
                $html .='|enum_value:"'.$enum_set->getFullName().'"';
            }
            $html .= '}}';
        }
        return $html;
    }
    /**
     * 入力HTMLソースの取得
     */
    public function getInputSource ($var_name='$forms.entry')
    {
        $html = '{{input name="'.$this->getName().'" type="'.$this->getAttr("type").'"';
        if ($enum_set = $this->getEnumSet()) {
            $html .=' enum="'.$enum_set->getFullName().'"';
        }
        if ($this->getAttr("type")=="password") {
            $html .=' autocomplete="new-password"';
        }
        $html .= '}}';
        if ($this->getAttr("type")=="file") {
            $html .= ' {{if '.$var_name.'.'.$this->getName().'}}<a href="{{'.
                $var_name.'.'.$this->getName().'}}" target="_blank">ファイル</a>{{/if}}';
        }
        return $html;
    }
    /**
     * $form_entryの定義行の取得
     */
    public function getEntryFormFieldDefSource ()
    {
        $def = array();
        $def[] = '"label"=>"'.$this->getLabel().'"';
        if ($this->getAttr("type")=="file") {
            $def[] = '"storage"=>"public"';
        }
        return '            "'.$this->getName().'" => array('.implode(', ',$def).'),'."\n";

    }
    /**
     * $colsの定義行の取得
     */
    public function getColDefSource ()
    {
        $def = (array)$this->getAttr("def");
        $def["comment"] = $this->getAttr("label");
        if ($this->getAttr("type")=="checklist" && $def["type"]=="text" && ! $def["format"]) {
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
        return '        "'.$this->getName().'" => array('.implode(', ',$def).'),'."\n";
    }
}
