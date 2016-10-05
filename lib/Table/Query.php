<?php
namespace R\Lib\Table;

use ArrayObject;

class Query extends ArrayObject
{
    static $keys = array(
        // FROM/INTO句に指定される実テーブル名
        "table" => array(),
        // AS句で指定されるテーブルの別名、Hydrate時にも参照される
        "alias" => array(),
        // JOIN句
        "joins" => array(),
        // SELECT構文の各SQL句
        "fields" => array(),
        "group_by" => array(),
        "order_by" => array(),
        "offset" => array(),
        "limit" => array(),
        // WHERE句
        "conditions" => array(),
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
            if ($key=="condition") { $key = "conditions"; }
            if ($key=="assoc_field") { $key = "assoc_fields"; }
            if ($key=="assoc_value") { $key = "assoc_values"; }

            if ( ! isset(static::$keys[$key])) {
                report_error("メソッドの定義がありません",array(
                    "class" => get_class($this),
                    "method_name" => $method_name,
                    "args_count" => count($args),
                ));
            }

            // fieldsであれば、既存の値を削除して値を設定
            if (($op=="add" || $op=="set" || $op=="remove") && $key=="fields") {
                $fields = array();
                // 引数の指定はValues/Value/Key,Valueの3パターン
                if ( ! $args[0]) {
                    return;
                } elseif (is_array($args[0])) {
                    $fields = $args[0];
                } elseif (count($args)==1) {
                    $fields = array($args[0]);
                } elseif (count($args)==2) {
                    $fields = array($args[1] => $args[0]);
                }
                foreach ($fields as $key => $value) {
                    // 既存の値を削除
                    if ( ! is_numeric($key)) {
                        if (isset($this["fields"][$key])) {
                            unset($this["fields"][$key]);
                        }
                    } elseif (($i = array_search($value,(array)$this["fields"]))!==false) {
                        unset($this["fields"][$i]);
                    }
                    if ($op=="remove") {
                        continue;
                    }
                    // 指定された値を追加
                    if ( ! is_numeric($key)) {
                        $this["fields"][$key] = $value;
                    } else {
                        $this["fields"][] = $value;
                    }
                }
                return;

            // get*であればgetter
            } elseif ($op=="get") {
                if (count($args)==0) {
                    return $this[$key];
                }

            // set*であればsetter
            } elseif ($op=="set") {
                if (count($args)==0) {
                    unset($this[$key]);
                    return;
                } elseif (count($args)==1) {
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
                    unset($this[$key][$args[0]]);
                    return;
                }
            }
        }

        report_error("メソッドの定義がありません",array(
            "class" => get_class($this),
            "method_name" => $method_name,
            "args_count" => count($args),
        ));
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
    public function join ($table, $alias=null, $on=array(), $type="LEFT")
    {
        if (is_array($on)) {
            $on = array($on);
        }
        $this["joins"][] = array(
            "table" => $table,
            "alias" => $alias ? $alias : $table,
            "conditions" => $on,
            "type" => $type,
        );
    }

    /**
     * @setter
     * conditionsを設定する
     */
    public function where ($k,$v=false)
    {
        if ($v === false) {
            $this["conditions"][] = $k;
        } else {
            $this["conditions"][$k] = $v;
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
