<?php
namespace R\Lib\Query;

/**
 * SELECT文の結果 1行の結果レコード
 */
class QueryResultRecord extends QueryResult
{
    protected $record_set = null;
    protected $record_index = null;

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // Table::resultRecord_メソッドの呼び出し
        $result_method_name = "resultRecord_".$method_name;
        if (method_exists($this->table, $result_method_name)) {
            array_unshift($args,$this);
            return call_user_func_array(array($this->table,$result_method_name), $args);
        }

        return parent::__call($method_name, $args);
    }

    /**
     * @setter
     */
    public function setRecordSet ($record_set, $record_index)
    {
        $this->record_set = $record_set;
        $this->record_index = $record_index;
    }
}
