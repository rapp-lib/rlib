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
        $values = array();
        if (isset($this->field_value)) {
            $values = is_array($this->field_value) ? $this->field_value : array($this->field_value);
        } elseif (isset($this->attrs["value"])) {
            $values = is_array($this->attrs["value"]) ? $this->attrs["value"] : array($this->attrs["value"]);
        }
        $options = array();
        if ($enum_name = $this->attrs["enum"]) {
            $parent_key = $group ? $group : false;
            foreach (app()->enum($enum_name,$parent_key) as $k=>$v) {
                $selected = in_array($k,$values);
                $options[] = array(
                    "selected" => $selected,
                    "checked" => $selected,
                    "value" => $k,
                    "label" => $v,
                );
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
        } elseif ($init_values = $this->attrs["values"]) {
            foreach ($init_values as $k=>$v) {
                $selected = in_array($k,$values);
                $options[] = array(
                    "selected" => $selected,
                    "checked" => $selected,
                    "value" => $k,
                    "label" => $v,
                );
            }
        } elseif ($values) {
            foreach ($values as $k) {
                $selected = in_array($k,$values);
                $options[] = array(
                    "selected" => $selected,
                    "checked" => $selected,
                    "value" => $k,
                    "label" => "",
                );
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
        // pluginに対応するJS呼び出し
        if ($plugins = $attrs["plugins"]) {
            unset($attrs["plugins"]);
            $attrs["data-rui-plugins"] = json_encode($plugins);
            // 必要なプラグインの読み込み
            $assets = app()->http->getServedRequest()->getUri()->getWebroot()->getAssets();
            foreach ($plugins as $plugin_name => $plugin_params) $assets->required('input_plugin.'.$plugin_name);
        }
        // type=selectであれば選択肢構築
        if ($this->attrs["type"]=="select") {
            $option_html = array();
            $option_html[] = tag("option");
            foreach ($this->getOptions($this->attrs["parent_key"]) as $option) {
                $option_attrs = array("value"=>$option["value"]);
                if ($option["selected"]) {
                    $option_attrs["selected"] = "selected";
                }
                $option_html[] = tag("option",$option_attrs,$option["label"]);
            }
            unset($attrs["type"]);
            unset($attrs["value"]);
            $this->html = tag("select",$attrs,implode('',$option_html));
        // type=checklistであれば選択肢構築
        } elseif ($this->attrs["type"]=="checklist") {
            $option_html = array();
            foreach ($this->getOptions($this->attrs["parent_key"]) as $option) {
                $option_attrs = $attrs;
                $option_attrs["name"] = $option_attrs["name"]."[]";
                $option_attrs["type"] = "checkbox";
                $option_attrs["value"] = $option["value"];
                if ($option["selected"]) {
                    $option_attrs["checked"] = "checked";
                }
                $option_html[] = tag("label",array(),tag("input",$option_attrs).tag("span",array(),$option["label"]));
            }
            unset($attrs["type"]);
            $this->html = implode('',$option_html);
        // type=radioselectであれば選択肢構築
        } elseif ($this->attrs["type"]=="radioselect") {
            $option_html = array();
            foreach ($this->getOptions($this->attrs["parent_key"]) as $option) {
                $option_attrs = $attrs;
                $option_attrs["name"] = $option_attrs["name"];
                $option_attrs["type"] = "radio";
                $option_attrs["value"] = $option["value"];
                if ($option["selected"]) {
                    $option_attrs["checked"] = "checked";
                }
                $option_html[] = tag("label",array(),tag("input",$option_attrs).tag("span",array(),$option["label"]));
            }
            unset($attrs["type"]);
            $this->html = implode('',$option_html);
        // type=textareaであれば、タグの様式変更
        } elseif ($this->attrs["type"]=="textarea") {
            $value = isset($this->field_value) ? $this->field_value : $attrs["value"];
            unset($attrs["type"]);
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
        // type=checkboxであれば、checkedに値を反映
        } elseif ($this->attrs["type"]=="checkbox") {
            if ( ! strlen($attrs["value"])) {
                $attrs["value"] = "1";
            }
            if ($attrs["value"] == $this->field_value) {
                $attrs["checked"] = "checked";
            } else {
                unset($attrs["checked"]);
            }
            $this->html = tag("input",$attrs);
        // type=passwordであれば、入力値を戻さない
        } elseif ($this->attrs["type"]=="password") {
            $this->html = tag("input",$attrs);
        // type=dateであれば、入力値の形式を日付型に整形
        } elseif ($this->attrs["type"]=="date") {
            $attrs["value"] = isset($this->field_value) ? $this->field_value : $attrs["value"];
            if (strlen($attrs["value"])) {
                $date = new \DateTime($attrs["value"]);
                $attrs["value"] = $date->format('Y-m-d');
            }
            $this->html = tag("input",$attrs);
        // その他のtypeは標準のタグ表示
        } else {
            $attrs["value"] = isset($this->field_value) ? $this->field_value : $attrs["value"];
            $this->html = tag("input",$attrs);
        }
    }
}
