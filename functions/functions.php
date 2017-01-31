<?php

// -- 各クラスのインスタンス取得

    /**
     *
     */
    function app_init ($container_class, $init_params)
    {
        $GLOBALS["R_CONTAINER"] = new $container_class();
        $GLOBALS["R_CONTAINER"]->init($init_params);
    }
    /**
     *
     */
    function app ()
    {
        return $GLOBALS["R_CONTAINER"];
    }
    /**
     * @deprecated
     */
    function auth ()
    {
        report_warning("@deprecated");
        return app()->auth;
    }
    /**
     * @alias
     */
    function table ($table_name)
    {
        return app()->table($table_name);
    }
    /**
     * @alias
     */
    function route ($route_name)
    {
        return app()->route($route_name);
    }
    /**
     * @deprecated
     */
    function form ()
    {
        report_warning("@deprecated");
        return app()->form;
    }
    /**
     * @deprecated
     */
    function enum ($enum_set_name=false, $group=false)
    {
        report_warning("@deprecated");
        return app()->enum($enum_set_name, $group);
    }
    /**
     * @deprecated
     */
    function enum_select ($value, $enum_set_name=false, $group=false)
    {
        report_warning("@deprecated");
        return app()->enum_select($value, $enum_set_name, $group);
    }
    /**
     * @deprecated
     */
    function asset () {
        report_warning("@deprecated");
        return app()->asset;
    }
    /**
     * @deprecated
     */
    function file_storage ()
    {
        report_warning("@deprecated");
        return app()->file_storage;
    }
    /**
     * @alias
     */
    function builder ()
    {
        return app()->builder;
    }
    /**
     * @alias
     */
    function util ($class_name, $constructor_args=false)
    {
        return app()->util($class_name, $constructor_args);
    }
    /**
     * @alias
     */
    function extention ($extention_group, $extention_name)
    {
        return app()->extention($extention_group, $extention_name);
    }
    /**
     * @alias
     */
    function redirect ($url, $params=array(), $anchor=null) {
        return app()->response->redirect($url, $params, $anchor);
    }

// -- 配列操作

    /**
     * is_arrayをオブジェクト配列も許容できるように拡張
     */
    function is_arraylike ( & $arr)
    {
        return is_array($arr) || $arr instanceof \ArrayAccess;
    }
    /**
     * ドット記法で配列の値を設定する
     */
    function array_set ( & $ref, $key, $value)
    {
        $key_parts = explode(".",$key);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                $ref = array();
            }
            $ref = & $ref[$key_part];
        }
        $ref = $value;
    }
    /**
     * ドット記法で配列の値を再帰的に追加する
     */
    function array_add ( & $ref, $key, $value=null)
    {
        // keyを配列で指定した場合
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                array_add($ref, $k, $v);
            }
        // valueを配列で指定した場合
        } elseif (is_array($value)) {
            $ref_sub = & array_get_ref($ref, $key);
            foreach ($value as $k => $v) {
                array_add($ref_sub, $k, $v);
            }
        } else {
            array_set($ref, $key, $value);
        }
    }
    /**
     * ドット記法で配列の値が設定されているか確認
     */
    function array_isset ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return false;
            }
            $ref = & $ref[$key_part];
        }
        return isset($ref[$key_last]);
    }
    /**
     * ドット記法で配列の値を取得する
     */
    function array_get ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return null;
            }
            $ref = & $ref[$key_part];
        }
        return isset($ref[$key_last]) ? $ref[$key_last] : null;
    }
    /**
     * ドット記法で配列の参照を取得する
     */
    function & array_get_ref ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                $ref = array();
            }
            $ref = & $ref[$key_part];
        }
        return $ref[$key_last];
    }
    /**
     * ドット記法で配列の値を削除する
     */
    function array_unset ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        $key_last = array_pop($key_parts);
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                return;
            }
            $ref = & $ref[$key_part];
        }
        unset($ref[$key_last]);
    }
    /**
     * 再帰的に空白要素を削除する
     */
    function array_clean ( & $ref)
    {
        if ( ! is_arraylike($ref)) {
            return $ref;
        }
        foreach ($ref as $k => & $v) {
            if (is_array($v)) {
                array_clean($v);
            }
            if (is_array($v) && count($v)===0) {
                unset($ref[$k]);
            } elseif (is_string($v) && strlen($v)===0) {
                unset($ref[$k]);
            } elseif ( ! isset($v)) {
                unset($ref[$k]);
            }
        }
    }
    /**
     * 配列を完全なドット記法配列に変換する
     */
    function array_dot ( & $ref)
    {
        $result = array();
        foreach ($ref as $k=>$v) {
            if (is_arraylike($v)) {
                foreach (array_dot($v) as $k_inner=>$v_inner) {
                    $result[$k.".".$k_inner] = $v_inner;
                }
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

// -- 移行する

    /**
     *
     */
    function str_camelize ($str) {

        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }

    //-------------------------------------
    // URLの組み立て
    function url ($base_url=null, $params=array(), $anchor=null) {
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

    //-------------------------------------
    // HTMLタグの組み立て
    function tag ($name, $attrs=null, $content=null) {

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
