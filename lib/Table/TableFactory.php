<?php
namespace R\Lib\Table;

/**
 * Tableインスタンス生成クラス
 */
class TableFactory
{
    public function __invoke ($table_name)
    {
        return $this->factory($table_name);
    }

    private $reserved_attrs = array();
    /**
     * Tableのインスタンスを作成
     */
    public function factory ($table_name)
    {
        $def = $this->getTableDef($table_name);
        $table = new $def["class"];
        foreach ($this->reserved_attrs as $key=>$value) $table->setAttr($key, $value);
        return $table;
    }
    /**
     * Tableのインスタンス生成時に設定する値の予約
     */
    public function reserveAttr ($name, $value)
    {
        $this->reserved_attrs[$name] = $value;
    }

// -- Table定義の取得

    /**
     * Tableの定義を取得
     */
    public function getTableDef($table_name)
    {
        $class = 'R\App\Table\\'.str_camelize($table_name)."Table";
        if ( ! class_exists($class)) {
            report_error("Tableクラスがありません",array(
                "table_name" => $table_name,
                "class" => $class,
            ));
        }
        $def = $class::getDef();
        $def["class"] = $class;
        return $def;
    }
    /**
     * クラス名からTable名を逆引き
     */
    public function getAppTableNameByClass($class)
    {
        return preg_match('!^\\\\?R\\\\App\\\\Table\\\\(.+?)Table$!', $class, $_) ? $_[1] : null;
    }
    /**
     * Table定義の一覧を取得
     */
    public function collectTableDefs(array $dirs)
    {
        foreach ($dirs as $dir) {
            foreach (glob($dir."/*") as $file) {
                require_once($file);
            }
        }
        $defs = array();
        foreach (get_declared_classes() as $class) {
            if (preg_match('!^'.preg_quote('R\App\Table\\').'([\w\d]+)Table$!', $class, $_)) {
                $table_name = str_underscore($_[1]);
                $defs[$table_name] = $this->getTableDef($table_name);
            }
        }
        return $defs;
    }
}
