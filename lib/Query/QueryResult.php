<?php
namespace R\Lib\Query;

use ArrayObject;

/**
 * SQLの結果
 */
class QueryResult extends ArrayObject
{
    protected $result_res;
    protected $table;

    protected $pager = array();

    /**
     * @override
     */
    public function __construct ($result_res, $table)
    {
        $this->result_res = $result_res;
        $this->table = $table;
    }

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        // Table::result_メソッドの呼び出し
        $result_method_name = "result_".$method_name;
        if (method_exists($this->table, $result_method_name)) {
            return call_user_func_array(array($this->table,$result_method_name), $args);
        }

        report_error("メソッドの定義がありません",array(
            "class" => get_class($this),
            "method_name" => $method_name,
            "chain_method_name" => $chain_method_name,
        ));
    }

    /**
     * @setter
     */
    public function setPager ($pager)
    {
        $this->pager = $pager;
    }

    /**
     * @getter
     */
    public function getPager ()
    {
        return $this->pager;
    }

    /**
     * 1結果レコードのFetch
     */
    public function fetch ()
    {
        $ds = $this->getDBI()->get_datasource();
        $ds->_result =$this->result_res;

        // 結果セットが無効であればnullを返す
        if ( ! $ds->hasResult()) {
            return null;
        }

        $ds->resultSet($ds->_result);
        $result =$ds->fetchResult();

        // データがなければnullを返す
        if ( ! $result) {
            return null;
        }

        // 結果レコードの組み立て
        $record =new QueryResultRecord($this->result_res, $this->table);

        // レコードセットとの関連づけ
        $record_index = count($this);
        $this[$record_index] = $record;
        $record->setRecordSet($this,$record_index);

        // Hydrate
        $record->hydrate($result);

        return $record;
    }

    /**
     * 全結果レコードのFetch
     */
    public function fetchAll ()
    {
        while ($this->fetch());

        return $this;
    }
}
