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

    /**
     * Tableのインスタンスを作成
     */
    public function factory ($table_name)
    {
        if (is_string($table_name)) {
            $def = $this->getTableDef($table_name);
        } elseif (is_array($table_name)) {
            $def = $table_name;
            if ( ! $def["class"]) $def["class"] = '\R\Lib\Table\Table_Base';
        }
        $table = new $def["class"];
        return $table;
    }

// -- Table定義の取得

    /**
     * Tableの定義を取得
     */
    public function getTableDef($table_name)
    {
        $class = $this->getClassByAppTableName($table_name);
        $def = $class::getDef();
        $def["class"] = $class;
        return $def;
    }
    /**
     * Table名からクラス名を正引き
     */
    public function getClassByAppTableName($app_table_name)
    {
        $class = 'R\App\Table\\'.str_camelize($app_table_name)."Table";
        if ( ! class_exists($class)) {
            report_error("Tableクラスがありません",array(
                "table_name" => $app_table_name,
                "class" => $class,
            ));
        }
        return $class;
    }
    /**
     * クラス名からTable名を逆引き
     */
    public function getAppTableNameByClass($class)
    {
        return preg_match('!^\\\\?R\\\\App\\\\Table\\\\(.+?)Table$!', $class, $_) ? $_[1] : null;
    }
    /**
     * 定義Table名からTable名を逆引き
     */
    public function getAppTableNameByDefTableName($def_table_name)
    {
        foreach ($this->collectTableDefs() as $app_table_name=>$def) {
            if ($def["table_name"]===$def_table_name) return $app_table_name;
        }
        return null;
    }
    /**
     * Table定義の一覧を取得
     */
    public function collectTableDefs($dirs=array())
    {
        $dirs[] = constant("R_APP_ROOT_DIR")."/app/Table";
        foreach ((array)$dirs as $dir) {
            foreach (glob($dir."/*") as $file) {
                require_once($file);
            }
        }
        $defs = array();
        foreach (get_declared_classes() as $class) {
            if (preg_match('!^'.preg_quote('R\App\Table\\').'([\w\d]+)Table$!', $class, $_)) {
                $table_name = $_[1];
                $defs[$table_name] = $this->getTableDef($table_name);
            }
        }
        return $defs;
    }
}
