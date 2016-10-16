<?php
namespace R\Lib\Form;

use R\Lib\Core\ArrayObject;

class FormFactory
{
    private static $instance = null;

    /**
     * インスタンスを取得
     */
    public static function getInstance ()
    {
        if ( ! self::$instance) {
            self::$instance = new FormFactory();
        }
        return self::$instance;
    }
    /**
     * 構成を指定してFormを作成
     */
    public function create ($def=array())
    {
        return new Form($def);
    }
    /**
     * FormRepositryクラスを元にFormRepositryProxy
     */
    public function createRepositry ($class_name)
    {
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }
        if ( ! $this->repositries[$class_name]) {
            $this->repositries[$class_name] = new FormRepositryProxy($this, $class_name);
        }
        return $this->repositries[$class_name];
    }
}
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
        $form_defs = $class_name::getFormDef();
        // Iteratorの
        $this->array_payload_keys = array_keys($form_defs);
        $this->array_payload_pos = 0;
    }
    /**
     * 指定されたform_nameのFormを作成/取得する
     */
    private function getForm ($form_name)
    {
        if ( ! isset($this->array_payload[$offset])) {
            $class_name = $this->repositry_class_name;
            $form_def = $class_name::getFormDef($form_name);
            $form = $this->factory->create($form_def);
            $this->array_payload[$offset] = $form;
        }
        return $this->array_payload[$offset];
    }
}
