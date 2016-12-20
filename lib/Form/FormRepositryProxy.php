<?php
namespace R\Lib\Form;

use ArrayObject;
use ArrayIterator;

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
     * 指定されたform_nameのFormを作成する
     */
    public function initValue ($form_name)
    {
        if ( ! $this->offsetExists($form_name)) {
            $class_name = $this->repositry_class_name;
            $form_def = $class_name::getFormDef($class_name,$form_name);
            $form = $this->factory->create($form_def);
            $this->offsetSet($form_name, $form);
        }
    }
    /**
     * 全てのFormを作成する
     */
    public function initValues ()
    {
        // 定義されている全てのform_nameを取得
        $class_name = $this->repositry_class_name;
        foreach ($class_name::getFormDef($class_name) as $form_name => $form_def) {
            $this->initValue($form_name);
        }
    }
    /**
     * @override ArrayAccess
     */
    public function offsetGet ($offset)
    {
        $this->initValue($offset);
        return parent::offsetGet($offset);
    }
    /**
     * @override ArrayObject
     */
    public function getIterator ()
    {
        return new FormRepositryProxyIterator($this);
    }
}

/**
 * FormRepositryProxyで使用するArrayIterator
 *      rewindでの要素初期化を実装する
 */
class FormRepositryProxyIterator extends ArrayIterator
{
    private $forms;
    public function __construct ($forms)
    {
        $this->forms = $forms;
        parent::__construct($forms);
    }
    /**
     * @override ArrayIterator
     */
    public function rewind ()
    {
        $this->forms->initValues();
        return parent::rewind();
    }
}
