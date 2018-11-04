<?php
namespace R\Lib\Table\Def;

/**
 * Table定義
 */
class TableDef
{
    // "table_name", "class", ds_name", "cols", "aliases", "fkey_routes", "rules"
    protected $def_attr_set;
    public function __construct($def_attr_set)
    {
        $this->def_attr_set = $def_attr_set;
    }
    /**
     * ChainHookの起動
     */
    public function __call($method_name, $args)
    {
        array_unshift($args, $this);
        return app("table.features")->call("chain", $method_name, $args);
    }
    /**
     * getter
     */
    public function getDefAttr($key)
    {
        return $this->def_attr_set[$key];
    }

    // -- 関係インスタンスの取得

    /**
     * QueryBuilderインスタンスの作成
     */
    public function makeQueryBuilder()
    {
        return app()->make("table.query_builder", array($this->def_attr_set["app_table_name"]));
    }
    /**
     * DBConnectionインスタンスの取得
     */
    public function getConnection()
    {
        return app("db")->getConnection($this->def_attr_set["ds_name"]);
    }

    // -- Table情報取得

    /**
     * スキーマ定義上のTable名の取得
     */
    public function getDefTableName()
    {
        return $this->def_attr_set["table_name"];
    }
    /**
     * アプリケーション上でのTable名の取得
     */
    public function getAppTableName()
    {
        return $this->def_attr_set["app_table_name"];
    }

    // -- Col定義の取得

    /**
     * Col属性の取得
     */
    public function getColAttr($col_name, $attr)
    {
        return $this->def_attr_set["cols"][$col_name][$attr];
    }
    /**
     * カラム名をすべて取得
     */
    public function getColNames()
    {
        return array_keys($this->def_attr_set["cols"]);
    }
    /**
     * 属性の指定されたカラム名をすべて取得
     */
    public function getColNamesByAttr($attr, $value=true)
    {
        $cols = array();
        foreach ($this->def_attr_set["cols"] as $col_name => $col) {
            if (($value===true && $col[$attr]) || $col[$attr]===$value) {
                $cols[] = $col_name;
            }
        }
        return $cols;
    }
    /**
     * ID属性の指定されたカラム名の取得
     */
    public function getIdColName()
    {
        $id_col_name = $this->getColNameByAttr("id");
        if ( ! $id_col_name) {
            report_error("idカラムが定義されていません", array(
                "table_def"=>$this,
            ));
        }
        return $id_col_name;
    }
    /**
     * 属性の指定されたカラム名の取得
     */
    public function getColNameByAttr($attr, $value=true)
    {
        $col_names = $this->getColNamesByAttr($attr, $value);
        return $col_names ? $col_names[0] : null;
    }
}
