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
    public static function factory ($table_name, $config=array())
    {
        $class = "R\\App\\Table\\".str_camelize($table_name)."Table";
        $table = new $class($config);
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