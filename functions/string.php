<?php


    //-------------------------------------
    //
    function str_underscore ($str) {

        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }

    //-------------------------------------
    //
    function str_camelize ($str) {

        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }

    //-------------------------------------
    // ランダム文字列の生成
    function rand_string (
            $length=8,
            $seed=null,
            $charmap='0123456789abcdefghijklmnopqrstuvwxyz') {

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

    //-------------------------------------
    // 文字列に配列要素をマッピング
    function str_template_array (
            $str,
            $arr,
            $pattern='!\{%([\._a-zA-Z0-9:]+)\}!e') {

        return preg_replace($pattern,'ref_array($arr,"$1")',$str);
    }

    //-------------------------------------
    // データの難読化
    function encrypt_string (
            $target,
            $key=7,
            $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {

        $map =str_split($chartable);
/*
        // 32bit compatible crc32
        $crc =abs(crc32($target));
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }
        $crc =sprintf('%02d',$crc%100);

        $target =$target.$crc;
*/
        $target =str_split($target);
        srand($key);

        foreach ($target as $i => $c) {

            shuffle($map);
            $target[$i] =$map[strpos($chartable,$c)];
        }

        $target =implode("",$target);

        return $target;
    }

    //-------------------------------------
    // データの難読復号化
    function decrypt_string (
            $target,
            $key=7,
            $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {

        $map =str_split($chartable);

        $target =str_split($target);

        srand($key);

        foreach ($target as $i => $c) {

            shuffle($map);
            $target[$i] =$chartable[strpos(implode("",$map),$c)];
        }

        $target =implode("",$target);
/*
        if (preg_match('!^(.*?)(..)$!',$target,$match)) {

            $target =$match[1];
            $crc_check =$match[2];
            $crc =sprintf('%02d',abs(crc32($target))%100);

            if ($crc_check == $crc) {

                return $target;
            }
        }
*/
        return $target;
    }


    /**
     * CLIに渡す文字列の構築
     */
    function cli_escape ($value) {

        $escaped_value =null;

        // 引数配列
        if (is_array($value)) {

            $escaped_value =array();

            foreach ($value as $k => $v) {

                if (is_string($k)) {

                    $escaped_value[] =cli_escape($k.$v);

                } else {

                    $escaped_value[] =cli_escape($v);
                }
            }

            $escaped_value =implode(" ",$escaped_value);

        // 文字列
        } elseif (is_string($value)) {

            $escaped_value =escapeshellarg($value);
        }

        return $escaped_value;
    }