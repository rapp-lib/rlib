<?php
namespace R\Lib\Table;

use ArrayObject;

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
        return call_user_func_array(array($this->table,"result_".$method_name), $args);
    }
}
