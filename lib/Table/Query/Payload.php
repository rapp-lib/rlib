<?php
namespace R\Lib\Table\Query;

class Payload extends \R\Lib\Table\Query
{
    protected $table_name;
    public function __construct($table_name)
    {
        $this->table_name = $table_name;
        parent::__construct();
        parent::setTable($table_name);
    }
    public function getDef()
    {
        return app()->tables[$this->table_name];
    }
    public function getTableName ()
    {
        return parent::getTable();
    }
    public function render()
    {
        $query = clone $this;
        if ( ! $query->getSkipBeforeRender()) {
            app("table.features")->emit("before_render", array($query));
            $query->setSkipBeforeRender(true);
        }
        $req = $query->getDef()->getConnection()->getRenderer()->render($query);
        $statement = app()->make("table.query_statement", array($req, $query));
        return $statement;
    }

// -- chain呼び出し

    public function __call($method_name, $args)
    {
        if (self::isAccessMethod($method_name)) {
            return parent::__call($method_name, $args);
        } else {
            array_unshift($args, $this);
            return app("table.features")->call("chain", $method_name, $args);
        }
    }
    private static $access_method_pattern = null;
    private static function isAccessMethod($method_name)
    {
        if ( ! static::$access_method_pattern) {
            $keys = array_merge(array_keys(static::$keys),
                array("field", "join", "value", "attr"));
            foreach ($keys as $i=>$key) $keys[$i] = camel_case($key);
            self::$access_method_pattern = '!^(get|set|add|remove)('.implode('|', $keys).')$!i';
        }
        return preg_match(self::$access_method_pattern, $method_name);
    }

    /**
     * @setter
     * joinsを設定する
     */
    public function join ($table, $on=array(), $type="LEFT")
    {
        if (is_array($table)) list($table, $alias) = $table;
        if (is_string($table)) $table = app()->tables[$table];
        if ($alias) $table->alias($alias);
        if ( ! is_array($on)) $on = array($on);
        $this["joins"][] = array($table, $on, $type);
    }
    /**
     * TableからSQLを組み立てる
     */
    private function _old_render()
    {
        $query = (array)$this->table->getQuery();
        foreach ((array)$query["fields"] as $k => $v) {
            // FieldsのAlias展開
            if ( ! is_numeric($k)) $query["fields"][$k] = array($v,$k);
            // Fieldsのサブクエリ展開
            if (is_object($v) && method_exists($v,"buildQuery")) {
                $query["fields"][$k] = $v = "(".$v->buildQuery("select").")";
            }
        }
        foreach ((array)$query["joins"] as $k => $v) {
            // Joinsのサブクエリ展開
            if (is_object($v[0]) && method_exists($v[0],"buildQuery")) {
                $v[0]->modifyQuery(function($sub_query) use (&$query, $k){
                    $sub_query_statement = $query["joins"][$k][0]->buildQuery("select");
                    if ($sub_query->getGroup()) {
                        //TODO: GroupBy付きのJOINでも異なるDB間でJOINできるようにする
                        $query["joins"][$k][0] = array("(".$sub_query_statement.")", $sub_query->getTableName());
                    } else {
                        $table_name = $sub_query->getTable();
                        // 異なるDB間でのJOIN時にはDBNAME付きのTable名とする
                        if ($query["dbname"]!==$sub_query["dbname"]) {
                            $table_name = $sub_query["dbname"].".".$table_name;
                        }
                        $alias = $sub_query->getAlias();
                        $query["joins"][$k][0] = $alias ? array($table_name, $alias) : $table_name;
                        if ($sub_query["where"]) $query["joins"][$k][1][] = $sub_query["where"];
                    }
                });
            }
        }
        // Updateを物理削除に切り替え
        if ($query["type"]=="update" && $query["delete"]) {
            unset($query["delete"]);
            $query["type"] = "delete";
        }
        // SQLBuilderの作成
        $db = $this->table->getConnection();
        $builder = new SQLBuilder(array(
            "quote_name" => array($db,"quoteName"),
            "quote_value" => array($db,"quoteValue"),
        ));
        return $builder->render($query);
    }
}
