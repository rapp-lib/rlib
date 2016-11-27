<?php
namespace R\Lib\Form;

/**
 *
 */
class InputField
{
    private $form;
    private $field_def;
    private $field_value;
    private $attrs;

    private $html;

    /**
     *
     */
    public function __construct ($form, $field_def, $field_value, $attrs)
    {
        $this->form = $form;
        $this->field_def = $field_def;
        $this->field_value = $field_value;
        $this->attrs = $attrs;
    }

    /**
     * HTMLを取得
     */
    public function getHtml ()
    {
        if ( ! isset($this->html)) {
            $this->buildHtml();
        }
        return $this->html;
    }

    /**
     * 選択肢の構成要素を取得
     */
    public function getOptions ($group=null)
    {
        $values = is_array($this->field_value) ? $this->field_value : array($this->field_value);
        $options = array();
        if ($enum_name = $this->attrs["enum"]) {
            if ($enum = enum($enum_name)) {
                foreach ($enum as $k=>$v) {
                    $selected = in_array($k,$values);
                    $options[] = array(
                        "selected" => $selected,
                        "checked" => $selected,
                        "value" => $k,
                        "label" => $v,
                    );
                }
            }
        // @deprecated 旧Listから値を取得
        } elseif ($list_name = $this->attrs["options"]) {
            $class = "\\".str_camelize($list_name)."List";
            if (class_exists($class)) {
                $instance = new $class();
                foreach ($instance->options($group) as $k=>$v) {
                    $selected = in_array($k,$values);
                    $options[] = array(
                        "selected" => $selected,
                        "checked" => $selected,
                        "value" => $k,
                        "label" => $v,
                    );
                }
            }
        }
        return $options;
    }

    /**
     * HTML要素を組み立てる
     */
    private function buildHtml ()
    {
        $attrs = $this->attrs;
        // type=selectであれば選択肢構築
        if ($this->attrs["type"]=="select") {
            $option_html = array();
            $option_html[] = tag("option");
            foreach ($this->getOptions() as $option) {
                $option_attrs = array("value"=>$option["value"]);
                if ($option["selected"]) {
                    $option_attrs["selected"] = "selected";
                }
                $option_html[] = tag("option",$option_attrs,$option["label"]);
            }
            $this->html = tag("select",$attrs,implode('',$option_html));
        // type=checklistであれば選択肢構築
        } elseif ($this->attrs["type"]=="checklist") {
            $option_html = array();
            foreach ($this->getOptions() as $option) {
                $option_attrs = $attrs;
                $option_attrs["name"] = $option_attrs["name"]."[]";
                $option_attrs["type"] = "checkbox";
                $option_attrs["value"] = $option["value"];
                if ($option["selected"]) {
                    $option_attrs["checked"] = "checked";
                }
                $option_html[] = tag("label",array(),tag("input",$option_attrs).tag("span",array(),$option["label"]));
            }
            $this->html = implode('',$option_html);
        // type=radioselectであれば選択肢構築
        } elseif ($this->attrs["type"]=="radioselect") {
            $option_html = array();
            foreach ($this->getOptions() as $option) {
                $option_attrs = $attrs;
                $option_attrs["name"] = $option_attrs["name"];
                $option_attrs["type"] = "radio";
                $option_attrs["value"] = $option["value"];
                if ($option["selected"]) {
                    $option_attrs["checked"] = "checked";
                }
                $option_html[] = tag("label",array(),tag("input",$option_attrs).tag("span",array(),$option["label"]));
            }
            $this->html = implode('',$option_html);
        // type=textareaであれば、タグの様式変更
        } elseif ($this->attrs["type"]=="textarea") {
            $value = isset($this->field_value) ? $this->field_value : $attrs["value"];
            unset($attrs["value"]);
            $this->html = tag("textarea",$attrs,(string)$value);
        // type=fileであれば、valueからhiddenを生成
        } elseif ($this->attrs["type"]=="file") {
            $value = isset($this->field_value) ? $this->field_value : $attrs["value"];
            unset($attrs["value"]);
            $hidden_html = tag("input",array(
                "type" => "hidden",
                "class" => "uploaded",
                "name" => $attrs["name"],
                "value" => $value,
            ));
            $this->html = tag("input",$attrs).$hidden_html;
        // type=passwordであれば、入力値を戻さない
        } elseif ($this->attrs["type"]=="password") {
            $this->html = tag("input",$attrs);
        // type=dateであれば、入力値の形式を日付型に整形
        } elseif ($this->attrs["type"]=="date") {
            $attrs["value"] = isset($this->field_value) ? $this->field_value : $attrs["value"];
            if ($date = longdate($attrs["value"])) {
                $attrs["value"] = $date["Y"].'-'.$date["m"].'-'.$date["d"];
            }
            $this->html = tag("input",$attrs);
        // その他のtypeは標準のタグ表示
        } else {
            $attrs["value"] = isset($this->field_value) ? $this->field_value : $attrs["value"];
            $this->html = tag("input",$attrs);
        }
    }
}
