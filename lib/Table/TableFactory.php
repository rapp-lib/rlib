<?php
namespace R\Lib\Table;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory
{
    private static $instance = null;

    /**
     * getDefのために使うTableインスタンス
     */
    private $tables = array();

    /**
     * Tableインスタンスを生成
     */
    public static function getInstance ($table_name=null)
    {
        // TableFactoryインスタンスを取得
        if ( ! isset($table_name)) {
            if ( ! isset($instance)) {
                $instance = new TableFactory;
            }
            return $instance;
        }
        // tableインスタンスの生成
        $class = "R\\App\\Table\\".str_camelize($table_name)."Table";
        if ( ! $table_name || ! class_exists($class)) {
            report_error("テーブルの指定が不正です",array(
                "table_name" => $table_name,
            ));
        }
        $table = new $class;
        return $table;
    }
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