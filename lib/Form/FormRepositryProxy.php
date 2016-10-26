<?php
namespace R\Lib\Form;

use ArrayObject;

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
    public function offsetGet ($offset)
    {
        $this->initOffset($offset);
        return parent::offsetGet($offset);
    }
    /**
     * @override Iterator
     */
    public function rewind ()
    {
        // 定義されている全てのform_nameを取得
        $class_name = $this->repositry_class_name;
        foreach ($class_name::getFormDef($class_name) as $form_name => $form_def) {
            $this->initOffset($form_name);
        }
        // 本来のrewindを呼び出す
        parent::rewind();
    }
    /**
     * 指定されたform_nameのFormを作成する
     */
    private function initOffset ($form_name)
    {
        if ( ! $this->offsetExists($form_name)) {
            $class_name = $this->repositry_class_name;
            $form_def = $class_name::getFormDef($class_name,$form_name);
            $form = $this->factory->create($form_def);
            $this->offsetSet($form_name, $form);
        }
    }
}
