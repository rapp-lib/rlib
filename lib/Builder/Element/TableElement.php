<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class TableElement extends Element_Base
{
    protected $cols = array();

    /**
     * @override
     */
    protected function init ()
    {
        foreach ((array)$this->getAttr("cols_all") as $col_name => $col_attrs) {
            $this->cols[$col_name] = new ColElement($col_name, $col_attrs, $this);
        }
    }
    /**
     * クラス名の取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Table";
    }
    /**
     * ColElementの取得
     */
    public function getCol ($col_name=null)
    {
        if ( ! $col_name) {
            return $this->cols;
        }
        return $this->cols[$col_name];
    }
    /**
     * テーブル定義を持つかどうか
     */
    public function hasDef ()
    {
        return ! $this->getAttr("nodef");
    }
}
