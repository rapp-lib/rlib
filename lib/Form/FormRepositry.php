<?php
namespace R\Lib\Form;

interface FormRepositry
{
    /**
     * Form構成を返す
     */
    public static function getFormDef ($class_name, $form_name=null);
}