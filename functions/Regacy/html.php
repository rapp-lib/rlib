<?php

    //-------------------------------------
    // 入力値の正規化
    function sanitize ($value) {
        report_warning("@deprecated sanitize");

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
        report_warning("@deprecated sanitize_decode");

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
