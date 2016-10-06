<?php

    //-------------------------------------
    //
    function array_extract ( & $arr) {

        if (is_arraylike($arr)) {

            foreach ($arr as $k => $_v) {

                if (is_arraylike($arr[$k])) {

                    array_extract($arr[$k]);
                }

                $ref =& ref_array($arr,$k);
                $ref =$arr[$k];
            }

        }
    }

    //-------------------------------------
    //
    function array_archive ( & $root, $node=null, $root_k=array()) {

        if ($node === null) {

            $node =$root;
        }

        foreach (array_keys($node) as $k) {

            $root_k_copy =$root_k;
            $root_k_copy[] =$k;

            if (is_arraylike($node[$k])) {

                array_archive($root,$node[$k],$root_k_copy);

            } else {

                $root[implode($root_k_copy,'.')] =$node[$k];
            }
        }
    }

    //-------------------------------------
    //
    function & ref_array ( & $arrx, $path) {

        if (is_arraylike($path)) {

            foreach ($path as $k => $v) {

                $path[$k] =str_replace(".","..",$path[$k]);
            }

            $path_str =implode('.',$path);

        } else {

            $path_str =$path;

            $path =str_replace("..",'__DELIM__',$path);
            $path =explode('.', $path);

            foreach ($path as $k => $v) {

                $path[$k] =str_replace("__DELIM__",".",$path[$k]);
            }
        }

        $_list =& $arrx;

        foreach ($path as $i => $key) {

            if (( ! preg_match('!^0\d+!',$key) && is_numeric($key)
                    && intval($key) > 0) || $key === '0') {

                $key =intval($key);

            } elseif ($key == "[]") {

                $key =$_list
                        ? max(array_keys($_list))+1
                        : 0;
            }

            if (count($path)-1 !== $i && ! isset($_list[$key])) {

                $_list[$key] =array();
            }

            $_list =& $_list[$key];
        }

        return $_list;
    }

    //-------------------------------------
    // Registryの中で配列を保持できるようにKeyをエスケープ
    function array_escape ($arr) {

        $arr_escaped =array();

        foreach ($arr as $k => $v) {

            if (is_arraylike($v)) {

                $v =array_escape($v);
            }

            $k =str_replace('.','..',$k);

            $arr_escaped[$k] =$v;
        }

        return $arr_escaped;
    }

    //-------------------------------------
    // 配列をregisry同様のストアとして利用する
    function & array_registry ( & $arr, $name=null ,$value=null, $options=array()) {

        // [Deprecated] $escapeとして指定を行っていた場合の処理
        if (is_bool($options)) {

            $escape =$options;
            $options =array("escape"=>$escape);
        }

        // 全取得
        if ($name === null) {

            return $arr;

        // 配列指定（name）
        } elseif (is_arraylike($name)) {

            foreach ($name as $a_name => $a_value) {

                array_registry($arr,$a_name,$a_value,$options);
            }

            return $arr;
        }

        // 必須定義（!...）
        if ($must_def =preg_match('/^!(.+)$/',$name,$match)) {

            $name =$match[1];
        }

        // 値の取得
        if ($value === null) {

            // 参照を解決（値で参照）
            if ($name === false) {

                $ref =$arr;

            } elseif (isset($options["escape"])) {

                $ref =$arr[$name];

            } else {

                $ref =ref_array($arr, $name);
            }

            // 必須エラー
            if ($must_def && $ref === null) {

                report_error("Registry must-be defined.",array(
                    "name" =>$name
                ));
            }

            return $ref;
        }

        // 参照解決
        if ($name === false) {

            $ref =& $arr;

        } elseif ($options["escape"]) {

            $ref =& $arr[$name];

        } else {

            $ref =& ref_array($arr, $name);
        }

        // 消去（value=false）
        if ($value === false) {

            if ($name === false) {

                $arr =array();

            } elseif ($options["escape"]) {

                unset($arr[$name]);

            } else {

                if (preg_match('!^(.*?[^\.])\.([^\.]+)$!',$name,$match)) {

                    $ref =& ref_array($arr, $match[1]);
                    unset($ref[$match[2]]);

                } else {

                    unset($arr[$name]);
                }
            }

        // 配列指定（value）
        } elseif (is_arraylike($value)) {

            if ( ! is_arraylike($ref)) {

                $ref =array();
            }

            // 配列指定の値をマージしない指定がある場合、上書き
            if ($options["no_array_merge"]) {

                $ref =$value;

            } else {

                foreach ($value as $a_name => $a_value) {

                    array_registry($ref,$a_name,$a_value,$options);
                }
            }

        // 値の設定
        } else {

            $ref =$value;
        }

        return $ref;
    }

    //-------------------------------------
    // 最初の要素を取得
    function array_first ($arr) {

        if (is_arraylike($arr) && $arr) {

            $keys =array_keys($arr);
            return $arr[array_shift($keys)];

        } else {

            return null;
        }
    }

    //-------------------------------------
    // 最後の要素を取得
    function array_last ($arr) {

        if (is_arraylike($arr) && $arr) {

            $keys =array_keys($arr);
            return $arr[array_pop($keys)];

        } else {

            return null;
        }
    }

    //-------------------------------------
    // 値を強制的に配列に変換する
    function to_array ($value) {

        // 配列はそのまま返す
        if (is_arraylike($value)) {

            return $value;

        // オブジェクトは展開
        } elseif (is_object($value)) {

            return get_object_vars($value);

        // null/falseは空の配列に変換
        } elseif ($value === null || $value === false) {

            return array();

        // unserialize可能であれば展開
        } elseif (is_string($value) && ($unserialized =@unserialize($value)) !== false) {

            if ($value === "b:0;") {

                return false;
            }

            return $unserialized;
        }

        // その他はすべて第一要素に入れて配列で返す
        return array($value);
    }