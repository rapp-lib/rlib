<?php

// -- 移植予定

    /**
     * PageからURLを得る（主にRedirectやHREFに使用）
     */
    function page_to_url ($page, $url_params=array(), $anchor=null)
    {
        if (preg_match('!^\.(.*)$!', $page, $match)) {
            $request_page_id = app()->http->getServedRequest()->getUri()->getPageId();
            $part = explode(".", $request_page_id, 2);
            $page = $part[0].".".($match[1] ?: $part[1]);
        }
        return app()->http->getWebroot()->uri("id://".$page, $url_params, $anchor);
    }
    /**
     * PathからURLを得る（主にRedirectやHREFに使用）
     */
    function path_to_url ($path, $url_params=array(), $anchor=null)
    {
        return app()->http->getWebroot()->uri("path://".$path, $url_params, $anchor);
    }
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
     * データの難読化
     */
    function encrypt_string ($target, $key=7)
    {
        report_warning("@deprecated encrypt_string");
        $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
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
     */
    function decrypt_string ($target, $key=7)
    {
        report_warning("@deprecated decrypt_string");
        $chartable="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
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
