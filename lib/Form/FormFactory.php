<?php
namespace R\Lib\Form;

use ArrayObject;

class FormFactory extends ArrayObject
{
    public function __construct ()
    {
    }
    public function offsetGet ($ext_controller_name)
    {
        $ext_class_name = "R\\App\\Controller\\".str_camelize($ext_controller_name)."Controller";
        return $this->getRepositry($ext_class_name);
    }
    /**
     * 構成を指定してFormを作成
     */
    public function create ($def=array())
    {
        return new FormContainer($def);
    }

// -- FormRepositry操作

    private $current_repositry = null;
    private $repositries = array();
    /**
     * FormRepositryクラスをFormRepositryProxyとして登録
     */
    public function addRepositry ($class_name)
    {
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }
        if ( ! $this->repositries[$class_name]) {
            $this->repositries[$class_name] = new FormRepositryProxy($this, $class_name);
            $this->current_repositry = $this->repositries[$class_name];
        }
        return $this->current_repositry;
    }
    /**
     * FormRepositryProxyを取得
     */
    public function getRepositry ($class_name=null)
    {
        if (is_object($class_name)) {
            $class_name = get_class($class_name);
        }
        if ( ! isset($class_name)) {
            return $this->current_repositry;
        }
        if ( ! $this->repositries[$class_name]) {
            $this->repositries[$class_name] = new FormRepositryProxy($this, $class_name);
        }
        return $this->repositries[$class_name];
    }
}
