<?php

    //-------------------------------------
    // 文字列に配列要素をマッピング
    function str_template_array (
            $str,
            $arr,
            $pattern='!\{%([\._a-zA-Z0-9:]+)\}!e') {
        report_warning("@deprecated str_template_array");

        return preg_replace($pattern,'ref_array($arr,"$1")',$str);
    }