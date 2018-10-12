<?php
namespace R\Lib\Table;

//use \ArrayObject;
use R\Lib\Core\ArrayObject;

/**
 * SELECT文の結果 1行の結果レコード
 */
class Record extends ArrayObject
{
    protected $table = null;
    /**
     * @override
     */
    public function __construct ($table)
    {
        $this->table = $table;
    }
    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // Table::record_メソッドの呼び出し
        array_unshift($args,$this);
        $record_method_name = "record_".$method_name;
        return call_user_func_array(array($this->table,$record_method_name),$args);
    }
    /**
     * @override
     */
    public function offsetGet ($key)
    {
        if ( ! $this->offsetExists($key)) $this->table->beforeGetRecordValue($this, $key);
        return parent::offsetGet($key);
    }
}
