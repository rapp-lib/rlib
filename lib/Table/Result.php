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
     * @getter
     */
    public function getTable ()
    {
        return $this->table;
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

    private $__release_status = 0;
    /**
     * メモリ解放
     */
    public function __release ()
    {
        if ($this->__release_status) return;
        $this->__release_status = 1;
        foreach ($this as $k=>$v) {
            if (is_object($v)) $v->__release();
            unset($this[$k]);
        }
        if ($this->table) {
            $this->table->__release();
            unset($this->table);
        }
    }
}
