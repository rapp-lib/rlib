<?php
namespace R\Lib\Builder\Element;

/*
$form = $pageset->getSearchForm();
$form = $pageset->getInputForm();
$form = $pageset->getCsvForm();
*/
class FormElement extends Element_Base
{
    public function init ()
    {
        // Field登録
        foreach ($this->getAttr("fields") as $field_name=>$field_attrs) {
            $this->children["field"][$page_name] = new FieldElement($field_name, $field_attrs, $this);
        }
    }
    public function getFields ()
    {
        return (array)$this->children["field"];
    }
    public function renderFormHtml ()
    {
        return "";
    }
    public function renderFormDef ()
    {
        return "";
    }
}
