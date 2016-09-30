<?php
namespace R\Lib\Query;

class Query
{
    public $query = array();

    /**
     * @override
     */
    public function __call ($method_name, $args=array())
    {
        if (preg_match("!^([get|set|add|remove])(.*?)$!",$method_name,$match)) {
            $op = $match[1];
            $key = str_underscore($match[2]);

            // get*であればgetter
            if ($op=="get") {
                if (count($args)==0) {
                    return $this->query_array[$key];
                }

            // set*であればsetter
            } elseif ($op=="set") {
                if (count($args)==0) {
                    unset($this->query_array[$key]);
                } elseif (count($args)==1) {
                    $this->query_array[$key] = $args[0];
                } elseif (count($args)==2) {
                    $this->query_array[$key][$args[0]] = $args[1];
                }

            // add*であれば配列として要素を追加
            } elseif ($op=="add") {
                if (count($args)==1) {
                    $this->query_array[$key][] = $args[0];
                }

            // remove*であれば要素を削除
            } elseif ($op=="remove") {
                if (count($args)==0) {
                    unset($this->query_array[$key]);
                } elseif (count($args)==1) {
                    unset($this->query_array[$key][$args[0]]);
                }
            }
        }
    }

    /**
     * @setter
     * tableを設定する
     */
    public function table ($table, $alias=false)
    {
        if (is_array($table)) {
            $this->query_array["table"] = $table[0];
            $this->query_array["alias"] = $table[1];
        } else if ($alias !== false) {
            $this->query_array["table"] = $table;
            $this->query_array["alias"] = $alias;
        } else {
            $this->query_array["table"] = $table;
        }
    }

    /**
     * @setter
     * joinsを設定する
     */
    public function join ($join_query_array)
    {
        $this->query_array["joins"][] = $join_query_array;
    }

    /**
     * @setter
     * conditionsを設定する
     */
    public function where ($k,$v=false)
    {
        if (is_array($k) || $v === false) {
            $this->query_array["conditions"][] = $k;
        } else {
            $this->query_array["conditions"][$k] = $v;
        }
    }

    /**
     * @setter
     * valuesを1項目設定する
     */
    public function value ($k, $v=false)
    {
        if ($v!==false) {
            $this->query_array["values"][$k] = $v;
        } else {
            $this->query_array["values"][] = $k;
        }
    }

    /**
     * @setter
     * valuesを一括設定する
     */
    public function values ($values)
    {
        foreach ((array)$values as $k => $v) {
            $this->value($k, $v);
        }
    }

    /**
     * @setter
     * fieldsを1項目設定する
     */
    public function field ($k)
    {
        if ($i = array_search($v, $this->query_array["fields"])) {
            $this->query_array["fields"][$i] = $k;
        } else {
            $this->query_array["values"][] = $k;
        }
    }

    /**
     * @setter
     * fieldsを一括設定する
     */
    public function fields ($fields)
    {
        foreach ((array)$fields as $k) {
            $this->field($k);
        }
    }

    /**
     * クエリ配列の取得
     */
    public function getQueryArray ()
    {
        return $this->query_array;
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
                        $this->query_array[$k][] =$v_v;
                    // 連想配列ならば要素の上書き
                    } else {
                        $this->query_array[$k][$v_k] =$v_v;
                    }
                }
            // スカラならば上書き
            } else {
                $this->query_array[$k] =$v;
            }
        }
    }
}