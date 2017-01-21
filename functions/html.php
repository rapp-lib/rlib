<?php

    //-------------------------------------
    // URL内パラメータの置換処理
    function url_param_replace ($match) {

        $replaced =$match[0];

        $tmp_url_params =& ref_globals("tmp_url_params");

        if (isset($tmp_url_params[$match[1]])) {

            $replaced =$tmp_url_params[$match[1]];
            unset($tmp_url_params[$match[1]]);
        }

        return $replaced;
    }

    //-------------------------------------
    // URL内パラメータの整列処理
    function url_param_ksort_recursive ( & $params) {

        if ( ! is_array($params)) {

            return;
        }

        ksort($params);

        foreach ($params as & $v) {

            url_param_ksort_recursive($v);
        }
    }

    //-------------------------------------
    // URL上でのパラメータ名の配列表現の正規化
    function param_name ($param_name) {

        if (preg_match('!^([^\[]+\.[^\[]+)([\[].*?)?!',$param_name,$match)) {

            $stack =explode(".",$match[1]);
            $param_name =array_shift($stack)."[".implode("][",$stack)."]".$match[2];
        }

        return $param_name;
    }

    //-------------------------------------
    // 入力値の正規化
    function sanitize ($value) {

        if (is_array($value)) {

            foreach ($value as $k => $v) {

                $value[$k] =sanitize($v);
            }

        } elseif (is_string($value)) {

            $value =str_replace(
                    array("&","<",">",'"',"'"),
                    array("&amp;","&lt;","&gt;","&quot;","&apos;"),
                    $value);
        }

        return $value;
    }

    //-------------------------------------
    // 入力値の逆正規化
    function sanitize_decode ($value) {

        if (is_array($value)) {

            foreach ($value as $k => $v) {

                $value[$k] =sanitize_decode($v);
            }

        } elseif (is_string($value)) {

            $value =str_replace(
                    array("&amp;","&lt;","&gt;","&quot;","&apos;"),
                    array("&","<",">",'"',"'"),
                    $value);
        }

        return $value;
    }
