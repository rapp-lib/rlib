<?php
namespace R\Lib\Util;
use ArrayObject;

class Arr
{
    /**
     * is_arrayをオブジェクト配列も許容できるように拡張
     */
    public static function is_arraylike ( & $arr)
    {
        return is_array($arr) || $arr instanceof \ArrayAccess;
    }
    /**
     * 配列が連想配列ではなく連番配列であるか判定
     */
    public static function is_seqarray ( & $arr)
    {
        $last = 0;
        foreach ($arr as $k=> & $v) if ( ! is_numeric($k) || $k!=$last++) return false;
        return true;
    }
    /**
     * ドット記法で配列の値を再帰的に追加する
     */
    public static function array_add ( & $ref, $key, $value=array())
    {
        // keyを配列で指定した場合
        if (\R\Lib\Util\Arr::is_arraylike($key)) {
            // 連番添え字の場合上書きせず追加
            if (\R\Lib\Util\Arr::is_seqarray($key)) {
                $offset = count($ref)==0 ? 0 : (int)max(array_keys($ref))+1;
                for ($i=0; $i<count($key); $i++) {
                    \R\Lib\Util\Arr::array_add($ref, $offset+$i, $key[$i]);
                }
            } else {
                foreach ($key as $k => $v) {
                    \R\Lib\Util\Arr::array_add($ref, $k, $v);
                }
            }
        } else {
            if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) $ref = array();
            // valueを配列で指定した場合
            if (\R\Lib\Util\Arr::is_arraylike($value)) {
                $ref_sub = & \R\Lib\Util\Arr::array_get_ref($ref, $key);
                // 連番添え字の場合上書きせず追加
                if (\R\Lib\Util\Arr::is_seqarray($value)) {
                    $offset = count($ref_sub)==0 ? 0 : (int)max(array_keys($ref_sub))+1;
                    for ($i=0; $i<count($value); $i++) {
                        \R\Lib\Util\Arr::array_add($ref_sub, $offset+$i, $value[$i]);
                    }
                } else {
                    foreach ($value as $k => $v) {
                        \R\Lib\Util\Arr::array_add($ref_sub, $k, $v);
                    }
                }
            } else {
                $ref_sub = & \R\Lib\Util\Arr::array_get_ref($ref, $key);
                $ref_sub = $value;
            }
        }
    }
    /**
     * ドット記法で配列の値が設定されているか確認
     */
    public static function array_isset ( & $ref, $key, $flag=0)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ($flag & 1 && is_object($ref) && $ref instanceof \Closure) $ref = call_user_func($ref);
            if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) return false;
            $ref = & $ref[$key_part];
        }
        if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) return false;
        return array_key_exists($key_last, $ref);
    }
    /**
     * ドット記法で配列の値を取得する
     */
    public static function array_get ( & $ref, $key, $flag=0)
    {
        $key_parts = explode(".",$key);
        foreach ($key_parts as $key_part) {
            if ($flag & 1 && is_object($ref) && $ref instanceof \Closure) $ref = call_user_func($ref);
            if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) return null;
            $ref = & $ref[$key_part];
        }
        return $ref;
    }
    /**
     * ドット記法で配列の参照を取得する
     */
    public static function & array_get_ref ( & $ref, $key, $flag=0)
    {
        $key_parts = explode(".",$key);
        foreach ($key_parts as $key_part) {
            if ($flag & 1 && is_object($ref) && $ref instanceof \Closure) $ref = call_user_func($ref);
            if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) $ref = array();
            $ref = & $ref[$key_part];
        }
        return $ref;
    }
    /**
     * ドット記法で配列の値を削除する
     */
    public static function array_unset ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) {
                return;
            }
            $ref = & $ref[$key_part];
        }
        unset($ref[$key_last]);
    }
    /**
     * 再帰的に空白要素を削除する
     */
    public static function array_clean ( & $ref)
    {
        if ( ! \R\Lib\Util\Arr::is_arraylike($ref)) return $ref;
        foreach ($ref as $k => & $v) {
            if (\R\Lib\Util\Arr::is_arraylike($v)) \R\Lib\Util\Arr::array_clean($v);
            if (is_array($v) && count($v)===0) unset($ref[$k]);
            elseif (is_string($v) && strlen($v)===0) unset($ref[$k]);
            elseif ( ! isset($v)) unset($ref[$k]);
        }
    }
    /**
     * 配列を完全なドット記法配列に変換する
     */
    public static function array_dot ( & $ref)
    {
        $result = array();
        foreach ($ref as $k => & $v) {
            if (\R\Lib\Util\Arr::is_arraylike($v)) {
                foreach (\R\Lib\Util\Arr::array_dot($v) as $k_inner=>$v_inner) {
                    $result[$k.".".$k_inner] = $v_inner;
                }
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }
    /**
     * {K:V}構造の配列を[[K,V]]構造に変換する
     */
    public static function kvdict ( & $arr)
    {
        if ( ! \R\Lib\Util\Arr::is_arraylike($arr)) return $arr;
        $kvdict = array();
        foreach ($arr as $k=> & $v) {
            $kvdict[] = array($k, \R\Lib\Util\Arr::kvdict($v));
        }
        return $kvdict;
    }
}
