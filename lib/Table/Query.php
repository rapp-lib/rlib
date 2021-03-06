<?php
namespace R\Lib\Table;

use ArrayObject;

class Query extends ArrayObject
{
    static $keys = array(
        // DB名
        "dbname" => null,
        // select/insert/updateのSQL文の種類
        "type" => array(),
        // FROM/INTO句に指定される実テーブル名
        "table" => array(),
        // JOIN句
        "joins" => array(),
        // SELECT構文の各SQL句
        "fields" => array(),
        "group" => array(),
        "having" => array(),
        "order" => array(),
        "offset" => array(),
        "limit" => array(),
        // WHERE句
        "where" => array(),
        // UPDATE文のSET句、INSERT文のINTO/VALUES句
        "values" => array(),

        "delete" => array(),
        "assoc_values" => array(),
        "attrs" => array(),
        "skip_before_render"=>array(),
    );

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        if (preg_match("!^(get|set|add|remove)(.+)$!",$method_name,$match)) {
            $op = $match[1];
            $key = str_underscore($match[2]);

            // alias
            if ($key=="field") { $key = "fields"; }
            if ($key=="value") { $key = "values"; }
            if ($key=="join") { $key = "joins"; }
            if ($key=="attr") { $key = "attrs"; }

            if ( ! array_key_exists($key, static::$keys)) {
                report_error("メソッドの定義がありません",array(
                    "class" => get_class($this),
                    "op" => $op,
                    "key" => $key,
                    "method_name" => $method_name,
                    "args_count" => count($args),
                    "query" => $this,
                ));
            }

            // fieldsであれば、既存の値を削除して値を設定
            if (($op=="add" || $op=="set" || $op=="remove" || $op=="get") && $key=="fields") {
                $fields = array();
                // 引数の指定はFields / FieldName / Key(Alias),FieldNameの3パターン
                if (is_array($args[0])) {
                    $fields = $args[0];
                } elseif (count($args)==1) {
                    $fields = array($args[0]);
                } elseif (count($args)==2) {
                    $fields = array($args[1] => $args[0]);
                }
                foreach ($fields as $k => $v) {
                    // 既存の値を削除
                    if ( ! is_numeric($k)) {
                        if ($op=="get") {
                            return $this[$key][$k];
                        } else {
                            $this->unsetItem($this[$key], $k);
                        }
                    // FieldName指定時の削除処理
                    } elseif (($i = array_search($v,(array)$this[$key]))!==false) {
                        if ($op=="get") {
                            return $this[$key][$i];
                        } else {
                            $this->unsetItem($this[$key] ,$i);
                        }
                    // 既存FieldNameはゆれを含めて削除
                    } else {
                        $field_name = preg_match('!\.!',$v)
                            ? preg_replace('!^'.$this->getTableName().'\.!','',$v)
                            : $this->getTableName().".".$v;
                        if (($i = array_search($field_name,(array)$this[$key]))!==false) {
                            if ($op=="get") {
                                return $this[$key][$i];
                            } else {
                                $this->unsetItem($this[$key], $i);
                            }
                        }
                    }
                    if ($op=="remove" || $op=="get") {
                        continue;
                    }
                    // 指定された値を追加
                    if ( ! is_numeric($k)) {
                        $this[$key][$k] = $v;
                    } else {
                        $this[$key][] = $v;
                    }
                }
                return;

            // valuesであれば、配列で複数指定可能にする
            } elseif (($op=="add" || $op=="set") && $key=="values") {
                $values = array();
                // 引数の指定はValues/Value/Key,Valueの3パターン
                if (is_array($args[0])) {
                    $values = $args[0];
                } elseif (count($args)==1) {
                    $values = array($args[0]);
                } elseif (count($args)==2) {
                    $values = array($args[0] => $args[1]);
                }
                foreach ($values as $k => $v) {
                    // 既存FieldNameはゆれを含めて削除
                    $field_name = preg_match('!\.!',$k)
                        ? preg_replace('!^'.$this->getTableName().'\.!','',$k)
                        : $this->getTableName().".".$k;
                    if (isset($this[$key][$field_name])) {
                        $this->unsetItem($this[$key], $field_name);
                    }
                    $this[$key][$k] = $v;
                }
                return;

            // getValuesでField名を指定している場合、FieldNameのゆれを吸収する
            } elseif ($op=="get" && count($args)==1) {
                $k = $args[0];
                $value = $this[$key][$k];
                // 既存FieldNameはゆれを含めて取得
                if ( ! isset($value)) {
                    $field_name = preg_match('!\.!',$k)
                        ? preg_replace('!^'.$this->getTableName().'\.!','',$k)
                        : $this->getTableName().".".$k;
                    $value = $this[$key][$field_name];
                }
                return $value;

            // get*であればgetter
            } elseif ($op=="get") {
                if (count($args)==0) {
                    return $this[$key];
                } elseif (count($args)==1) {
                    return $this[$key][$args[0]];
                }

            // set*であればsetter
            } elseif ($op=="set") {
                if (count($args)==1) {
                    $this[$key] = $args[0];
                    return;
                } elseif (count($args)==2) {
                    $this[$key][$args[0]] = $args[1];
                    return;
                }

            // add*であれば配列として要素を追加
            } elseif ($op=="add") {
                if (count($args)==1) {
                    $this[$key][] = $args[0];
                    return;
                }

            // remove*であれば要素を削除
            } elseif ($op=="remove") {
                if (count($args)==0) {
                    unset($this[$key]);
                    return;
                } elseif (count($args)==1) {
                    $this->unsetItem($this[$key], $args[0]);
                    return;
                }
            }
        }

        report_error("メソッドの定義がありません",array(
            "class" => get_class($this),
            "method_name" => $method_name,
            "op" => $op,
            "args_count" => count($args),
            "query" => $this,
        ));
    }
    /**
     * @deprecated
     * PHP5.3.3以前でArrayObjectでネストした配列のUnsetが無効になるバグに対応するUnset処理
     */
    private function unsetItem ( & $array, $key)
    {
        \R\Lib\Util\Arr::array_unset($array, $key);
    }
    /**
     * @getter
     */
    public function getTable ()
    {
        return is_array($this["table"]) ? $this["table"][0] : $this["table"];
    }
    /**
     * @setter
     */
    public function setAlias ($alias)
    {
        if (is_array($this["table"])) $this["table"][1] = $alias;
        else $this["table"] = array($table, $alias);
    }
    /**
     * @getter
     */
    public function getTableName ()
    {
        return is_array($this["table"]) ? $this["table"][1] : $this["table"];
    }
    /**
     * @getter
     */
    public function getAlias ()
    {
        if (is_array($this["table"]) && $this["table"][0]!==$this["table"][1]) return $this["table"][1];
    }
    /**
     * @setter
     */
    public function setType ($type)
    {
        if ($type!="select" && $type!="insert" && $type!="update") {
            report_error("不正なQueryのtypeが指定されました",array(
                "type" => $type,
            ));
        }
        if ($this["type"] && $type!=$this["type"]) {
            report_error("Queryのtypeは変更できません",array(
                "current_type" => $this["type"],
                "type" => $type,
            ));
        }
        $this["type"] = $type;
    }
    /**
     * @setter
     * joinsを設定する
     */
    public function join ($table, $on=array(), $type="LEFT")
    {
        if ( ! is_array($on)) $on = array($on);
        $this["joins"][] = array($table, $on, $type);
    }
    /**
     * @getter
     * joinsを取得する
     */
    public function getJoinByName ($table)
    {
        foreach ((array)$this["joins"] as $join) {
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
            $this["where"][] = $k;
        } else {
            $this["where"][$k] = $v;
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
                        $this[$k][] =$v_v;
                    // 連想配列ならば要素の上書き
                    } else {
                        $this[$k][$v_k] =$v_v;
                    }
                }
            // スカラならば上書き
            } else {
                $this[$k] =$v;
            }
        }
    }

    private $__release_status = 0;
    /**
     * メモリ解放
     */
    public function __release ()
    {
        if ($this->__release_status) return;
        $this->__release_status = 1;
        if (is_object($this["table"])) {
            $this["table"]->__release();
            unset($this["table"]);
        }
    }
}
