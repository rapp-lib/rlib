<?php
namespace R\Lib\Table\Def;

class TableDefResolver
{
    /**
     * 参照可能な全Table名の配列を取得する
     */
    public function getAllTableNames()
    {
        $table_names = array();
        foreach ($this->getFinders() as $finder) {
            if ($finder["dir"]) {
                foreach (glob($finder["dir"]."/*.php") as $file) {
                    if (preg_match('!([\w\d]+)Table\.php$!', $file, $_)) {
                        $table_names[] = $finder["prefix"].$_[1];
                    }
                }
            }
        }
        return $table_names;
    }
    /**
     * Table名からTable定義配列データを取得
     */
    public function getTableDefAttrSet($table_name)
    {
        $class = $this->getTableClassByTableName($table_name);
        if ( ! class_exists($class)) {
            report_error("Tableクラスの定義がありません", array(
                "table_name"=>$table_name,
                "class"=>$class,
            ));
        }
        $def_attr_set = $class::getDef();
        $def_attr_set["class"] = $class;
        $def_attr_set["app_table_name"] = $table_name;
        return $def_attr_set;
    }
    /**
     * Table名からクラス名を正引き
     * (旧)TableFactory::getClassByAppTableName
     */
    public function getTableClassByTableName($table_name)
    {
        foreach ($this->getFinders() as $finder) {
            if ( ! $finder["prefix"] || strpos($table_name, $finder["prefix"])===0) {
                return $finder["namespace"].'\\'.$table_name.'Table';
            }
        }
        return null;
    }
    /**
     * クラス名からTable名を逆引き
     * (旧)TableFactory::getAppTableNameByClass
     */
    public function getTableNameByClass($class)
    {
        foreach ($this->getFinders() as $finder) {
            if (strpos($class, $finder["namespace"])===0) {
                if (preg_match('!([\w\d]+)Table^!', $class, $_)) {
                    return $finder["prefix"].$_[1];
                }
            }
        }
        return null;
    }
    /**
     * 物理Table名からTable名を逆引き
     * (旧)getAppTableNameByDefTableName
     */
    public function getTableNameByDefTableName($def_table_name)
    {
        // DefCollectionから全テーブル定義を参照する
        foreach (app()->tables as $table_name=>$table_def) {
            if ($table_def->getDefTableName() === $def_table_name) return $table_name;
        }
        return null;
    }

    // -- Finder管理

    protected $finders = array();
    /**
     * Finder登録
     */
    public function addFinder($finder)
    {
        $this->finders[] = $finder;
    }
    /**
     * Finder取得
     */
    protected function getFinders()
    {
        $finders = $this->finders;
        // 最後にデフォルトFinderを追加
        $finders[] = array(
            "prefix"=>"",
            "dir"=>constant("R_APP_ROOT_DIR")."/app/Table",
            "namespace"=>'\R\App\Table',
        );
        return $finders;
    }
}
