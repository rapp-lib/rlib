<?php

// -- 移植予定

    /**
     * PageからURLを得る（主にRedirectやHREFに使用）
     */
    function page_to_url ($page, $url_params=array(), $anchor=null)
    {
        return app()->route("page:".$page)->getUrl($url_params,$anchor);
    }
    /**
     * PathからURLを得る（主にRedirectやHREFに使用）
     */
    function path_to_url ($path, $url_params=array(), $anchor=null)
    {
        return app()->route("path:".$path)->getUrl($url_params,$anchor);
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
        report_warning("@deprecated rand_string");
        $charmap='0123456789abcdefghijklmnopqrstuvwxyz';
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
     * URLの組み立て
     */
    function url ($base_url=null, $params=array(), $anchor=null)
    {
        if (is_object($base_url) && method_exists($base_url, "getUrl")) {
            $base_url = $base_url->getUrl($params);
            $params = array();
        }
        $url =$base_url;
        // 文字列形式のURLパラメータを配列に変換
        if (is_string($params)) {
            parse_str($params,$tmp_params);
            $params =$tmp_params;
        }
        // アンカーの退避
        if (preg_match('!^(.*?)#(.*)$!',$url,$match)) {
            list(,$url,$old_anchor) =$match;
            if ($anchor === null) {
                $anchor =$old_anchor;
            }
        }
        // QSの退避
        if (preg_match('!^([^\?]+)\?(.*)!',$url,$match)) {
            list(,$url,$qs) =$match;
            parse_str($qs,$qs_params);
            $params =array_replace_recursive($qs_params, $params);
        }
        // URLパス内パラメータの置換
        $ptn_url_param ='!\[([^\]]+)\]!';
        if (preg_match($ptn_url_param,$url)) {
            $tmp_url_params =& ref_globals("tmp_url_params");
            $tmp_url_params =$params;
            $url =preg_replace_callback($ptn_url_param,"url_param_replace",$url);
            $params =$tmp_url_params;
            // 置換漏れの確認
            if (preg_match($ptn_url_param,$url)) {
                /*
                report_warning("URL params was-not resolved, remain",array(
                    "url" =>$url,
                    "base_url" =>$base_url,
                    "params" =>$params,
                ));
                */
            }
        }
        // QSの設定
        if ($params) {
            url_param_ksort_recursive($params);
            $url .=strpos($url,'?')===false ? '?' : '&';
            $url .=http_build_query($params,null,'&');
        }
        // アンカーの設定
        if (strlen($anchor)) {
            $url .='#'.$anchor;
        }
        return $url;
    }
    /**
     * URL内パラメータの置換処理
     */
    function url_param_replace ($match)
    {
        $replaced =$match[0];
        $tmp_url_params =& ref_globals("tmp_url_params");
        if (isset($tmp_url_params[$match[1]])) {
            $replaced =$tmp_url_params[$match[1]];
            unset($tmp_url_params[$match[1]]);
        }
        return $replaced;
    }
    /**
     * URL内パラメータの整列処理
     * @access private
     */
    function url_param_ksort_recursive ( & $params)
    {
        if ( ! is_array($params)) {
            return;
        }
        ksort($params);
        foreach ($params as & $v) {
            url_param_ksort_recursive($v);
        }
    }
    /**
     * URL上でのパラメータ名の配列表現の正規化
     * @access private
     */
    function param_name ($param_name)
    {
        if (preg_match('!^([^\[]+\.[^\[]+)([\[].*?)?!',$param_name,$match)) {
            $stack =explode(".",$match[1]);
            $param_name =array_shift($stack)."[".implode("][",$stack)."]".$match[2];
        }
        return $param_name;
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
                            $v =param_name($v);
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
                        $html .=param_name($k).'="'.$v.'" ';
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