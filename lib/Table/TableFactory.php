<?php
namespace R\Lib\Table;

use R\Lib\Core\Contract\InvokableProvider;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory implements InvokableProvider
{
    /**
     * @override InvokableProvider
     */
    public function invoke ($table_name)
    {
        return $this->factory($table_name);
    }
    /**
     * Tableのインスタンスを作成
     */
    public function factory ($table_name)
    {
        $class = 'R\App\Table\\'.str_camelize($table_name)."Table";
        if ( ! $table_name || ! class_exists($class)) {
            report_error("テーブルの指定が不正です",array(
                "table_name" => $table_name,
                "class" => $class,
            ));
        }
        $table = new $class;
        return $table;

    }
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
