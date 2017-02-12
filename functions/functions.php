<?php

// -- 各クラスのインスタンス取得

    /**
     *
     */
    function app_init ($container_class, $init_params)
    {
        $GLOBALS["R_CONTAINER"] = new $container_class();
        $GLOBALS["R_CONTAINER"]->init($init_params);
    }
    /**
     *
     */
    function app ()
    {
        return $GLOBALS["R_CONTAINER"];
    }
    /**
     * @alias
     */
    function table ($table_name)
    {
        return app()->table($table_name);
    }
    /**
     * @alias
     */
    function route ($route_name)
    {
        return app()->route($route_name);
    }
    /**
     * @alias
     */
    function auth ($role_name=false)
    {
        return app()->auth($role_name);
    }
    /**
     * @alias
     */
    function util ($class_name, $constructor_args=false)
    {
        return app()->util($class_name, $constructor_args);
    }
    /**
     * @alias
     */
    function extention ($extention_group, $extention_name)
    {
        return app()->extention($extention_group, $extention_name);
    }
    /**
     * @alias
     */
    function redirect ($url, $params=array(), $anchor=null) {
        return app()->response->redirect($url, $params, $anchor);
    }

// -- 配列操作

    /**
     * is_arrayをオブジェクト配列も許容できるように拡張
     */
    function is_arraylike ( & $arr)
    {
        return is_array($arr) || $arr instanceof \ArrayAccess;
    }
    /**
     * ドット記法で配列の値を再帰的に追加する
     */
    function array_add ( & $ref, $key, $value=array())
    {
        // keyを配列で指定した場合
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                array_add($ref, $k, $v);
            }
        } else {
            // 数値添え字の場合上書きせず追加
            if (is_numeric($key)) {
                if ( ! is_array($ref)) {
                    $ref = array();
                }
                $key = count($ref)==0 ? 0 : (int)max(array_keys($ref))+1;
            }
            // valueを配列で指定した場合
            if (is_array($value)) {
                $ref_sub = & array_get_ref($ref, $key);
                foreach ($value as $k => $v) {
                    array_add($ref_sub, $k, $v);
                }
            } else {
                $ref_sub = & array_get_ref($ref, $key);
                $ref_sub = $value;
            }
        }
    }
    /**
     * ドット記法で配列の値が設定されているか確認
     */
    function array_isset ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return false;
            }
            $ref = & $ref[$key_part];
        }
        if ( ! is_array($ref)) {
            return false;
        }
        return array_key_exists($key_last, $ref);
    }
    /**
     * ドット記法で配列の値を取得する
     */
    function array_get ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return null;
            }
            $ref = & $ref[$key_part];
        }
        return isset($ref[$key_last]) ? $ref[$key_last] : null;
    }
    /**
     * ドット記法で配列の参照を取得する
     */
    function & array_get_ref ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                $ref = array();
            }
            $ref = & $ref[$key_part];
        }
        return $ref[$key_last];
    }
    /**
     * ドット記法で配列の値を削除する
     */
    function array_unset ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return;
            }
            $ref = & $ref[$key_part];
        }
        unset($ref[$key_last]);
    }
    /**
     * 再帰的に空白要素を削除する
     */
    function array_clean ( & $ref)
    {
        if ( ! is_arraylike($ref)) {
            return $ref;
        }
        foreach ($ref as $k => & $v) {
            if (is_array($v)) {
                array_clean($v);
            }
            if (is_array($v) && count($v)===0) {
                unset($ref[$k]);
            } elseif (is_string($v) && strlen($v)===0) {
                unset($ref[$k]);
            } elseif ( ! isset($v)) {
                unset($ref[$k]);
            }
        }
    }
    /**
     * 配列を完全なドット記法配列に変換する
     */
    function array_dot ( & $ref)
    {
        $result = array();
        foreach ($ref as $k=>$v) {
            if (is_arraylike($v)) {
                foreach (array_dot($v) as $k_inner=>$v_inner) {
                    $result[$k.".".$k_inner] = $v_inner;
                }
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }
