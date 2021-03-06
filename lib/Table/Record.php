<?php
namespace R\Lib\Table;

use ArrayObject;

/**
 * SELECT文の結果 1行の結果レコード
 */
class Record extends ArrayObject
{
    const RESULT_INDEX = "*RESULT*";
    /**
     * @override
     */
    public function __construct ($result)
    {
        $this[static::RESULT_INDEX] = $result;
    }
    /**
     * @getter
     */
    public function getTable ()
    {
        return $this->getResult()->getTable();
    }
    /**
     * @getter
     */
    public function getResult ()
    {
        return parent::offsetGet(static::RESULT_INDEX);
    }
    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // Table::record_メソッドの呼び出し
        array_unshift($args, $this);
        $record_method_name = "record_".$method_name;
        return call_user_func_array(array($this->getTable(),$record_method_name),$args);
    }
    /**
     * @override
     */
    public function offsetGet ($key)
    {
        if ( ! $this->offsetExists($key)) $this->getTable()->beforeGetRecordValue($this, $key);
        return parent::offsetGet($key);
    }
    /**
     * @getter
     */
    public function getArrayCopy ()
    {
        $values = array();
        foreach (parent::getIterator() as $k=>$v) if ($k !== static::RESULT_INDEX) $values[$k] = $v;
        return $values;
    }
    /**
     * @getter
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getArrayCopy());
    }
    /**
     * @getter
     */
    public function __report()
    {
        return $this->getArrayCopy();
    }
}
