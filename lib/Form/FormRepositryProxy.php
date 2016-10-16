<?php
namespace R\Lib\Form;

use R\Lib\Core\ArrayObject;

class FormRepositryProxy extends ArrayObject
{
    private $factory;
    private $repositry_class_name;

    /**
     *
     */
    public function __construct ($factory, $repositry_class_name)
    {
        $this->factory = $factory;
        $this->repositry_class_name = $repositry_class_name;
    }
    /**
     * @override ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->getForm($offset);
    }
    /**
     * @override Iterator
     */
    public function rewind ()
    {
        // 定義されている全てのform_nameを取得
        $class_name = $this->repositry_class_name;
        $form_defs = $class_name::getFormDef($class_name);
        // Iteratorの
        $this->array_payload_keys = array_keys($form_defs);
        $this->array_payload_pos = 0;
    }
    /**
     * 指定されたform_nameのFormを作成/取得する
     */
    private function getForm ($form_name)
    {
        if ( ! isset($this->array_payload[$form_name])) {
            $class_name = $this->repositry_class_name;
            $form_def = $class_name::getFormDef($class_name,$form_name);
            $form = $this->factory->create($form_def);
            $this->array_payload[$form_name] = $form;
        }
        return $this->array_payload[$form_name];
    }
}
