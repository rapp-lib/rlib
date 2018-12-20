<?php
namespace R\Lib\Table\Query;

class Record extends \ArrayObject
{
    const RESULT_INDEX = "*RESULT*";
    /**
     * @inheritdoc
     */
    public function __construct($result)
    {
        $this[static::RESULT_INDEX] = $result;
    }
    /**
     * getter
     */
    public function getResult()
    {
        return parent::offsetGet(static::RESULT_INDEX);
    }
    /**
     * @inheritdoc
     */
    public function __call($method_name, $args)
    {
        array_unshift($args, $this);
        return app("table.features")->call("record", $method_name, $args);
    }
    /**
     * @inheritdoc
     */
    public function offsetGet($key)
    {
        // 添え字が存在している場合はそのまま返す
        if (parent::offsetExists($key)) {
            return parent::offsetGet($key);
        // Table.col形式であれば分割して探索
        } elseif (count($parts = explode(".", $key)) === 2) {
            return $this[$parts[0]][$parts[1]];
        // 添え字が存在しなければblank_colを呼んでから返す
        } else {
            app("table.features")->emit("blank_col", array($this, $key));
            if ( ! parent::offsetExists($key)) {
                foreach ($this->getResult() as $record) $record[$key] = null;
                report_warning("値の設定されないColの参照", array(
                    "query"=>$this->getResult()->getStatement()->getQuery(),
                    "col"=>$key,
                ));
            }
            return parent::offsetGet($key);
        }
    }
    /**
     * @inheritdoc
     */
    public function getArrayCopy()
    {
        $values = array();
        foreach (parent::getIterator() as $k=>$v) {
            if ($k !== static::RESULT_INDEX) $values[$k] = $v;
        }
        return $values;
    }
    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getArrayCopy());
    }
    /**
     * @inheritdoc
     */
    public function exchangeArray($input)
    {
        foreach (parent::getIterator() as $k=>$v) {
            if ($k !== static::RESULT_INDEX) unset($values[$k]);
        }
        foreach ($input as $k=>$v) {
            $this[$k] = $v;
        }
    }
    /**
     * Fetch結果データのマッピング
     */
    public function hydrate($data)
    {
        // QueryのFROMとなったテーブル以外の値は階層を下げる
        $query_table_name = $this->getResult()->getStatement()->getQuery()->getTableName();
        foreach ($data as $table_name => $values) {
            foreach ($values as $col_name => $value) {
                if ($query_table_name === $table_name || $table_name === 0) {
                    $this[$col_name] = $value;
                } else $this[$table_name.".".$col_name] = $value;
            }
        }
    }
    /**
     * @deprecated
     * カラムの値の取得（"テーブル名.カラム名"の様式解決）
     */
    public function getColValue ($key)
    {
        return $this[$key];
    }
}
