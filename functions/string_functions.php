<?php

    function str_camelize ($str)
    {
        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }
    function str_underscore ($str)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }
    function str_date ($string, $format="Y/m/d")
    {
        if ( ! strlen($string)) return "";
        $date = new \DateTime($string);
        return $date->format($format);
    }
    function rand_string ($length=8, $seed=null)
    {
        $charmap = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars = str_split($charmap);
        $string = "";
        if (isset($seed)) srand(crc32((string)$seed));
        for ($i=0; $i<$length; $i++) $string .=$chars[array_rand($chars)];
        return $string;
    }
