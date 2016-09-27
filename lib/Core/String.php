<?php
/*
    2016/07/06
        core/string.php内の全関数の移行完了
 */
namespace R\Lib\Core;


/**
 *
 */
class String {

    /**
     * [str_underscore description]
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public static function underscore ($str)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }

    /**
     * [camelize description]
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public static function camelize ($str)
    {
        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }

    /**
     * ランダム文字列の生成
     * @param  integer $length  [description]
     * @param  [type]  $seed    [description]
     * @param  string  $charmap [description]
     * @return [type]           [description]
     */
    public static function rand ($length=8, $seed=null, $charmap='0123456789abcdefghijklmnopqrstuvwxyz')
    {
        $chars =str_split($charmap);
        $string ="";

        $seed === null
                ? srand()
                : srand(crc32((string)$seed));

        for ($i=0; $i<$length; $i++) {
            $string .=$chars[array_rand($chars)];
        }

        return $string;
    }

    /**
     * 文字列に配列要素をマッピング
     * @param  [type] $str     [description]
     * @param  [type] $arr     [description]
     * @param  string $pattern [description]
     * @return [type]          [description]
     */
    public static function template ($str, $arr, $pattern='!\{%([\._a-zA-Z0-9:]+)\}!e')
    {
        return preg_replace($pattern,'ref_array($arr,"$1")',$str);
    }

    /**
     * データの難読化
     * @param  [type]  $target    [description]
     * @param  integer $key       [description]
     * @param  string  $chartable [description]
     * @return [type]             [description]
     */
    public static function encrypt ($target, $key=7,
        $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
    {
        $map =str_split($chartable);

        // 32bit compatible crc32
        $crc =abs(crc32($target));

        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }

        $crc =sprintf('%02d',$crc%100);

        $target =$target.$crc;
        $target =str_split($target);

        srand($key);

        foreach ($target as $i => $c) {
            shuffle($map);
            $target[$i] =$map[strpos($chartable,$c)];
        }

        $target =implode("",$target);

        return $target;
    }

    /**
     * データの難読復号化
     * @param  [type]  $target    [description]
     * @param  integer $key       [description]
     * @param  string  $chartable [description]
     * @return [type]             [description]
     */
    public static function decrypt ($target, $key=7,
        $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
    {
        $map =str_split($chartable);

        $target =str_split($target);

        srand($key);

        foreach ($target as $i => $c) {

            shuffle($map);
            $target[$i] =$chartable[strpos(implode("",$map),$c)];
        }

        $target =implode("",$target);

        if (preg_match('!^(.*?)(..)$!',$target,$match)) {

            $target =$match[1];
            $crc_check =$match[2];
            $crc =sprintf('%02d',abs(crc32($target))%100);

            if ($crc_check == $crc) {

                return $target;
            }
        }

        return null;
    }

    /**
     * CLIに渡す文字列の構築
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function cli ($value)
    {
        $escaped_value =null;

        // 引数配列
        if (is_array($value)) {

            $escaped_value =array();

            foreach ($value as $k => $v) {

                if (is_string($k)) {

                    $escaped_value[] =String::cli($k.$v);

                } else {

                    $escaped_value[] =String::cli($v);
                }
            }

            $escaped_value =implode(" ",$escaped_value);

        // 文字列
        } else if (is_string($value)) {

            $escaped_value =escapeshellarg($value);
        }

        return $escaped_value;
    }

    /**
     * 入力値の正規化
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function sanitizeRequest ($value) {

        if (is_array($value)) {

            foreach ($value as $k => $v) {

                $value[$k] =self::sanitizeRequest($v);
            }

        } else if (is_string($value)) {

            $value =str_replace(
                array("&","<",">",'"',"'"),
                array("&amp;","&lt;","&gt;","&quot;","&apos;"),
                $value);
        }

        return $value;
    }

    /**
     * 入力値の逆正規化
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function desanitizeRequest ($value) {

        if (is_array($value)) {

            foreach ($value as $k => $v) {

                $value[$k] =self::desanitizeRequest($v);
            }

        } else if (is_string($value)) {

            $value =str_replace(
                array("&amp;","&lt;","&gt;","&quot;","&apos;"),
                array("&","<",">",'"',"'"),
                $value);
        }

        return $value;
    }
}