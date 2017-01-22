<?php
namespace R\Lib\Table;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory
{
    /**
     * getDefのために使うTableインスタンス
     */
    private $tables = array();
    /**
     * Tableの構成を取得
     */
    public function getDef ($table_name, $col_name=null)
    {
        if ( ! isset($this->table[$table_name])) {
            $this->table[$table_name] = table($table_name);
        }
        if (isset($col_name)) {
            return $this->table[$table_name]->getColDef($col_name);
        } else {
            return $this->table[$table_name]->getDef();
        }
    }
}