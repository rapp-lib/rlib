<?php
namespace R\Lib\Enum;

use ArrayObject;
use ArrayIterator;

/**
 *
 */
class Enum_Base extends ArrayObject
{
    protected $set_name;
    protected $parent_key;

    /**
     *
     */
    public function __construct ($set_name, $parent_key=false)
    {
        $this->set_name = $set_name;
        $this->parent_key = $parent_key;
    }

    /**
     * 指定されたキーに対応する値を初期化
     */
    public function initValue ($offset)
    {
        // 初期化済みであれば省略
        if ( ! isset($offset) || parent::offsetExists($offset)) {
            return;
        }
        // value_*メソッドの定義があれば参照する
        if (method_exists($this, $method_name = "value_".$this->set_name)) {
            $this[$offset] = call_user_func(array($this, $method_name),$offset);
            return;
        }
        $this->initValues();
    }

    /**
     * 全てのキーの初期化を行う
     */
    public function initValues ($keys=false)
    {
        if ($this->parent_key!==false) {
            if (method_exists($this, $method_name = "layered_values_".$this->set_name)) {
                $values = call_user_func(array($this, $method_name), $this->parent_key, $keys);
                $this->setValues($values);
                return;
            } elseif (property_exists($this, $property_name = "layered_values_".$this->set_name)) {
                $this->setValues($this::$$property_name[$this->parent_key]);
                return;
            }
        } else {
            if (method_exists($this, $method_name = "values_".$this->set_name)) {
                $values = call_user_func(array($this, $method_name), $keys);
                $this->setValues($values);
                return;
            } elseif (property_exists($this, $property_name = "values_".$this->set_name)) {
                $this->setValues($this::$$property_name);
                return;
            }
        }
    }

    /**
     * 未初期化の値を設定する
     */
    public function setValues ($values)
    {
        foreach ($values as $key => $value) {
            if (parent::offsetExists($key)) {
                continue;
            }
            $this[$key] = $value;
        }
    }

    /**
     * Resultに含まれるcol_nameの値をキーとして収集して値の初期化を行う
     */
    public function retrieveKeysByResult ($result, $col_name)
    {
        $keys = array();
        foreach ($result as $record) {
            if (is_array($record[$col_name])) {
                foreach ($record[$col_name] as $value) {
                    $keys[$value] = $value;
                }
            } elseif ($value = $record[$col_name]) {
                $keys[$value] = $value;
            }
        }
        $this->initValues($keys);
    }

    /**
     * Recordに含まれるcol_nameの値をキーとして収集して値の初期化を行う
     */
    public function retrieveKeysByRecord ($record, $col_name)
    {
        $keys = array();
        if (is_array($record[$col_name])) {
            foreach ($record[$col_name] as $value) {
                $keys[$value] = $value;
            }
        } elseif ($value = $record[$col_name]) {
            $keys[$value] = $value;
        }
        $this->initValues($keys);
    }

    /**
     * @override ArrayObject
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
        return new EnumIterator($this);
    }
}

/**
 * Enum_Baseで使用するArrayIterator
 *      rewindでの要素初期化を実装する
 */
class EnumIterator extends ArrayIterator
{
    private $enum;
    public function __construct ($enum)
    {
        $this->enum = $enum;
        parent::__construct($enum);
    }
    /**
     * @override ArrayIterator
     */
    public function rewind ()
    {
        $this->enum->initValues();
        return parent::rewind();
    }
}
