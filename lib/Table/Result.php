<?php
namespace R\Lib\Table;

use R\Lib\Core\ArrayObject;

/**
 * SQLの結果
 */
class Result extends ArrayObject
{
    protected $table;
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
        // Table::result_メソッドの呼び出し
        array_unshift($args,$this);
        $result_method_name = "result_".$method_name;
        return call_user_func_array(array($this->table,$result_method_name),$args);
    }
    /**
     * @override
     */
    public function offsetGet ($key)
    {
        if ( ! $this->offsetExists($key)) $this->table->beforeGetResultValue($this, $key);
        return parent::offsetGet($key);
    }
}
