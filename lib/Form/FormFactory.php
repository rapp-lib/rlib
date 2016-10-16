<?php
namespace R\Lib\Form;

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
        return new FormContainer($def);
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
