<?php
namespace R\Lib\Query;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory
{
    /**
     * Tableインスタンスを生成
     */
    public static function factory ($table_name, $config=array())
    {
        $class = "R\\App\\Table\\".str_camelize($table_name)."Table";
        $config["table_name"] = $table_name;
        $table = new $class($config);
        return $table;
    }
}