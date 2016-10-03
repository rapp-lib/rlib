<?php
namespace R\Lib\Table;

use ArrayObject;

/**
 * SELECT文の結果 1行の結果レコード
 */
class Record extends ArrayObject
{
    protected $table = null;

    /**
     * @override
     */
    public function __construct ($table, $data=null)
    {
        $this->table = $table;
        if (isset($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // Table::record_メソッドの呼び出し
        array_unshift($args,$this);
        return call_user_func_array(array($this->table,"record_".$method_name), $args);
    }
}
