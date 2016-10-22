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
     * HTML要素を取得
     */
    public function getHtml ()
    {
        $html_parts = $this->getHtmlParts();
        return $html_parts["formatted"];
    }

    /**
     * HTML要素を組み立てずに配列で取得
     */
    public function getHtmlParts ()
    {
        $extention = extention("InputType", $this->attrs["type"]);
        return call_user_func_array($extention,array($this->field_value,$this->attrs));
    }
}
