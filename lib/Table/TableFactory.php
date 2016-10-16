<?php
namespace R\Lib\Table;

use R\Util\ClassCollector;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory
{
    /**
     * Tableインスタンスを生成
     */
    public static function factory ($table_name)
    {
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
     * Tableクラスを全て取得
     */
    public static function collectTables ()
    {
        $tables = array();
        $classes = ClassCollector::findClassInNamespace("R\\App\\Table\\");

        foreach ($classes as $i => $class) {
            if (preg_match('!^R\\\\App\\\\Table\\\\([a-zA-Z0-9]+)Table!',$class,$match)) {
                $tables[] = $match[1];
            }
        }
        return $tables;
    }
}