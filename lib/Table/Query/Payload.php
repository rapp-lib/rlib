<?php
namespace R\Lib\Table\Query;

class Payload
{
    protected $table_name;
    protected $data = array();
    public function __construct($table_name)
    {
        $this->table_name = $table_name;
        $this->data["table"] = $table_name;
    }
    public function getDef()
    {
        return app()->tables[$this->table_name];
    }
    public function getPayload()
    {
        return $this->data;
    }
    public function makeBuilder()
    {
        return app()->make("table.query_builder", array($this));
    }
    public function render()
    {
        $query = clone $this;
        if ( ! $query->getSkipBeforeRender()) {
            app("table.features")->emit("before_render", array($query));
            $query->setSkipBeforeRender(true);
        }
        $req = $query->getDef()->getConnection()->getRenderer()->render($query->getPayload());
        $statement = app()->make("table.query_statement", array($req, $query));
        return $statement;
    }

// -- 旧実装

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
        $this->data["joins"][] = array($table, $on, $type);
    }
    public function setType ($type)
    {
        $this->data["type"] = $type;
    }
    /**
     * @inheritdoc
     */
    public function __call($method_name, $args=array())
    {
        if (preg_match("!^(get|set|add|remove)(.+)$!", $method_name, $_)) {
            $op = $_[1];
            $key = snake_case($_[2]);
            $argc = count($args);
        } else {
            report_error("メソッドの定義がありません",array(
                "class" => get_class($this),
                "method_name" => $method_name,
                "op" => $op,
                "args_count" => count($args),
                "query" => $this,
            ));
        }
        // alias
        if ($key=="field") { $key = "fields"; }
        if ($key=="value") { $key = "values"; }
        if ($key=="join") { $key = "joins"; }
        if ($key=="attr") { $key = "attrs"; }

        // fieldsであれば、既存の値を削除して値を設定
        if ($key=="fields") {
            $fields = array();
            // 引数の指定はFields / FieldName / Key(Alias),FieldNameの3パターン
            if (is_array($args[0])) $fields = $args[0];
            elseif (count($args)==1) $fields = array($args[0]);
            elseif (count($args)==2) $fields = array($args[1] => $args[0]);
            foreach ($fields as $k => $v) {
                // 既存の値を削除
                if ( ! is_numeric($k)) {
                    if ($op=="get") return $this->data[$key][$k];
                    else unset($this->data[$key][$k]);
                // FieldName指定時の削除処理
                } elseif (($i = array_search($v,(array)$this->data[$key]))!==false) {
                    if ($op=="get") return $this->data[$key][$i];
                    else unset($this->data[$key][$i]);
                // 既存FieldNameはゆれを含めて削除
                } else {
                    $field_name = preg_match('!\.!',$v)
                        ? preg_replace('!^'.$this->getTableName().'\.!','',$v)
                        : $this->getTableName().".".$v;
                    if (($i = array_search($field_name,(array)$this->data[$key]))!==false) {
                        if ($op=="get") return $this->data[$key][$i];
                        else unset($this->data[$key][$i]);
                    }
                }
                // remove/getは値を設定しない
                if ($op=="remove" || $op=="get") continue;
                // set/addであれば指定された値を追加
                elseif ( ! is_numeric($k)) $this->data[$key][$k] = array($v, $k);
                else $this->data[$key][] = $v;
            }

        // valuesであれば、配列で複数指定可能にする
        } elseif (($op=="add" || $op=="set") && $key=="values") {
            $values = array();
            // 引数の指定はValues/Value/Key,Valueの3パターン
            if (is_array($args[0])) $values = $args[0];
            elseif (count($args)==1) $values = array($args[0]);
            elseif (count($args)==2) $values = array($args[0] => $args[1]);
            foreach ($values as $k => $v) {
                // 既存FieldNameはゆれを含めて削除
                $field_name = preg_match('!\.!',$k)
                    ? preg_replace('!^'.$this->getTableName().'\.!','',$k)
                    : $this->getTableName().".".$k;
                if (isset($this->data[$key][$field_name])) {
                    unset($this->data[$key][$field_name]);
                }
                $this->data[$key][$k] = $v;
            }

        // getValuesでField名を指定している場合、FieldNameのゆれを吸収する
        } elseif ($op=="get" && count($args)==1) {
            $k = $args[0];
            $value = $this->data[$key][$k];
            // 既存FieldNameはゆれを含めて取得
            if ( ! isset($value)) {
                $field_name = preg_match('!\.!',$k)
                    ? preg_replace('!^'.$this->getTableName().'\.!','',$k)
                    : $this->getTableName().".".$k;
                $value = $this->data[$key][$field_name];
            }
            return $value;

        // get*であればgetter
        } elseif ($op=="get") {
            if (count($args)==0) return $this->data[$key];
            elseif (count($args)==1) return $this->data[$key][$args[0]];

        // set*であればsetter
        } elseif ($op=="set") {
            if (count($args)==1) $this->data[$key] = $args[0];
            elseif (count($args)==2) $this->data[$key][$args[0]] = $args[1];

        // add*であれば配列として要素を追加
        } elseif ($op=="add") {
            if (count($args)==1) $this->data[$key][] = $args[0];

        // remove*であれば要素を削除
        } elseif ($op=="remove") {
            if (count($args)==0) unset($this->data[$key]);
            elseif (count($args)==1) unset($this->data[$key][$args[0]]);
        }
    }
    /**
     * @getter
     */
    public function getTable ()
    {
        return is_array($this->data["table"]) ? $this->data["table"][0] : $this->data["table"];
    }
    /**
     * @setter
     */
    public function setAlias ($alias)
    {
        if (is_array($this->data["table"])) $this->data["table"][1] = $alias;
        else $this->data["table"] = array($table, $alias);
    }
    /**
     * @getter
     */
    public function getTableName ()
    {
        return is_array($this->data["table"]) ? $this->data["table"][1] : $this->data["table"];
    }
    /**
     * @getter
     */
    public function getAlias ()
    {
        if (is_array($this->data["table"]) && $this->data["table"][0]!==$this->data["table"][1]) return $this->data["table"][1];
    }
    /**
     * @getter
     * joinsを取得する
     */
    public function getJoinByName ($table)
    {
        foreach ((array)$this->data["joins"] as $join) {
            if (is_string($join[0]) && $join[0]==$table) return $join;
            elseif (is_array($join[0]) && $join[0][1]==$table) return $join;
            elseif (is_object($join[0]) && $join[0]->getQueryTableName()==$table) return $join;
        }
        return null;
    }
    /**
     * @setter
     * conditionsを設定する
     */
    public function where ($k,$v=false)
    {
        if ($v === false) {
            $this->data["where"][] = $k;
        } else {
            $this->data["where"][$k] = $v;
        }
    }
    /**
     * クエリの統合（上書きを避けつつ右を優先）
     */
    public function merge ($query)
    {
        foreach ($query as $k => $v) {
            // 配列ならば要素毎に追加
            if (is_array($v)) {
                foreach ($v as $v_k => $v_v) {
                    // 数値添え字ならば最後に追加
                    if (is_numeric($v_k)) {
                        $this->data[$k][] =$v_v;
                    // 連想配列ならば要素の上書き
                    } else {
                        $this->data[$k][$v_k] =$v_v;
                    }
                }
            // スカラならば上書き
            } else {
                $this->data[$k] =$v;
            }
        }
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

    public function __report()
    {
        return $this->data;
    }
}
