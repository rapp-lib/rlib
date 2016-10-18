<?php
namespace R\Lib\Form;

class FormFactory
{
    private static $instance = null;

    private $current_repositry = null;
    private $repositries = array();

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
            $this->current_repositry = new FormRepositryProxy($this, $class_name);
            $this->repositries[$class_name] = $this->current_repositry;
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
        return isset($class_name) ? $this->repositries[$class_name] : $this->current_repositry;
    }
}
