<?php
namespace R\Lib\Table\Def;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * Table定義のCollection
 */
class TableDefCollection implements ArrayAccess, IteratorAggregate
{
    public function offsetExists($key)
    {
        return $this->find($key) !== null;
    }
    public function offsetGet($key)
    {
        $def = $this->find($key);
        if ( ! $def) {
            report_error("Table定義がありません", array("table_name"=>$key));
        }
        return $def;
    }
    public function offsetSet($key, $value)
    {
        return;
    }
    public function offsetUnset($key)
    {
        return;
    }
    public function getIterator()
    {
        return new ArrayIterator($this->retreive());
    }

    protected $retreived = true;
    protected $table_defs = array();
    /**
     * 参照可能な全Table定義を収集して取得
     */
    protected function retreive()
    {
        if ( ! $this->retreived) {
            $table_names = app("table.def_resolver")->getAllTableNames();
            foreach ($table_names as $table_name) $this->find($table_name);
            $this->retreived = true;
        }
        return $this->table_defs;
    }
    /**
     * 指定されたTable定義を収集して取得
     */
    protected function find($table_name)
    {
        if ( ! $this->table_defs[$table_name]) {
            $def_attr_set = app("table.def_resolver")->getTableDefAttrSet($table_name);
            $this->table_defs[$table_name] = app()->make("table.def", array($def_attr_set));
        }
        return $this->table_defs[$table_name];
    }
}
