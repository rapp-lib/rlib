<?php
namespace R\Lib\Form;

class FormFactory
{
    private $current_repositry = null;
    private $repositries = array();

// -- Form作成

    /**
     * 構成を指定してFormを作成
     */
    public function create ($def=array())
    {
        return new FormContainer($def);
    }

// -- FormRepositry操作

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
