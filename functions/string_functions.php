<?php

    /**
     *
     */
    function str_camelize ($str)
    {
        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }
    /**
     *
     */
    function str_underscore ($str)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }
    /**
     * ランダム文字列の生成
     */
    function rand_string ($length=8, $seed=null)
    {
        $charmap = '0123456789abcdefghijklmnopqrstuvwxyz';
        $chars = str_split($charmap);
        $string = "";
        $seed === null
                ? srand()
                : srand(crc32((string)$seed));
        for ($i=0; $i<$length; $i++) {
            $string .=$chars[array_rand($chars)];
        }
        return $string;
    }
    /**
     * HTMLタグの組み立て
     */
    function tag ($name, $attrs=null, $content=null)
    {
        $html ='';
        if ( ! is_string($name)) {
            return htmlspecialchars($name);
        }
        $html .='<'.$name.' ';
        if ($attrs === null) {
        } elseif (is_string($attrs)) {
            $html .=$attrs.' ';
            report_warning("HTMLタグのattrsは配列で指定してください");
        } elseif (is_array($attrs)) {
            foreach ($attrs as $k => $v) {
                if ($v !== null) {
                    if (is_numeric($k)) {
                        $html .=$v.' ';
                    } else {
                        if (($name == "input" || $name == "textarea"
                                || $name == "select") && $k == "name") {
                        } elseif (is_array($v)) {
                            if ($k == "style") {
                                $style =array();
                                foreach ($v as $style_name => $style_attr) {
                                    if (is_numeric($style_name)) {
                                        $style .=$style_attr;
                                    } else {
                                        $style .=$style_name.':'.$style_attr.';';
                                    }
                                }
                                $v =$style;
                            } elseif ($k == "class") {
                                $v =implode(' ',$v);
                            } else {
                                $v =implode(',',$v);
                            }
                        }
                        if (is_object($v)) $v = "";
                        $v =str_replace(array("\r\n","\n",'"'),array(" "," ",'&quot;'),$v);
                        $html .=$k.'="'.$v.'" ';
                    }
                }
            }
        }
        if ($content === null) {
            $html .='/>';
        } elseif ($content === true) {
            $html .='>';
        } elseif ($content === false) {
            $html ='</'.$name.'>';
        } elseif (is_array($content)) {
            $html .='>';
            foreach ($content as $k => $v) {
                $html .=call_user_func_array("tag",(array)$v);
            }
            $html .='</'.$name.'>';
        } elseif (is_string($content)) {
            $html .='>';
            $html .=$content;
            $html .='</'.$name.'>';
        }
        return $html;
    }
