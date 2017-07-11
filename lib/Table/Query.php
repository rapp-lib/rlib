<?php
namespace R\Lib\Table;

use ArrayObject;

class Query extends ArrayObject
{
    static $keys = array(
        // DB名
        "dbname" => null,
        // FROM/INTO句に指定される実テーブル名
        "table" => array(),
        // AS句で指定されるテーブルの別名、Hydrate時にも参照される
        "alias" => array(),
        // JOIN句
        "joins" => array(),
        // SELECT構文の各SQL句
        "fields" => array(),
        "group" => array(),
        "order" => array(),
        "offset" => array(),
        "limit" => array(),
        // WHERE句
        "where" => array(),
        // UPDATE文のSET句、INSERT文のINTO/VALUES句
        "values" => array(),

        // select/insert/updateのSQL文の種類
        "type" => array(),
        // trueであればUPDATE文をDELETE文に変換する
        "delete" => array(),

        // fetch時、マッピングを行わない指定（NoFetch時に使用）
        "no_mapping" => array(),

        // fields/valuesに指定された項目で、テーブル定義に含まれないもの
        "assoc_fields" => array(),
        "assoc_values" => array(),
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
            if ($key=="where") { $key = "where"; }
            if ($key=="assoc_field") { $key = "assoc_fields"; }
            if ($key=="assoc_value") { $key = "assoc_values"; }

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
        array_unset($array, $key);
    }
    /**
     * @getter
     */
    public function getTableName ()
    {
        return strlen($this["alias"]) ? $this["alias"] : $this["table"];
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
        if ($table instanceof Table_Base) {
            $alias = $table->getQueryTableName();
            if ($alias && is_string($table) && $alias!==$table) {
                $table = array($table, $alias);
            }
        }
        if ( ! is_array($on)) {
            $on = array($on);
        }
        $this["joins"][] = array($table, $on, $type);
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
}
