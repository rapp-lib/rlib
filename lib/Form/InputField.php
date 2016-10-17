<?php
namespace R\Lib\Form;

/**
 *
 */
class InputField
{
    private $form;
    private $field_def;

    /**
     *
     */
    public function __construct ($form, $field_def)
    {
        $this->form = $form;
        $this->field_def = $field_def;
    }

    /**
     * HTML要素を組み立てずに配列で取得
     */
    public function getHtml ($attrs)
    {
        $value = $this->form->getValueByNameAttr($attrs["name"]);
        $html = call_user_func_array(extention("InputType", $attrs["type"]),array($value,$attrs));
        return $html;
    }
}
