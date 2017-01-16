<?php

// -- 各クラスのインスタンス取得

    /**
     * @facade R\Lib\Core\Application::getInstance
     */
    function app ()
    {
        return R\Lib\Core\Application::getInstance();
    }
    /**
     * @deprecated
     */
    function config ($key)
    {
        return app()->config($key);
    }
    /**
     * @deprecated
     */
    function request ()
    {
        return app()->request();
    }
    /**
     * @deprecated
     */
    function response ()
    {
        return app()->response();
    }
    /**
     * @facade R\Lib\Auth\AccountManager::getInstance
     */
    function auth ($name=false)
    {
        return R\Lib\Auth\AccountManager::getInstance($name);
    }
    /**
     * @facade R\Lib\Auth\AccountManager::getAccount
     */
    function auccount ($name=false)
    {
        return auth()->getAccount($name);
    }
    /**
     * @facade R\Lib\Table\TableFactory::getInstance
     */
    function table ($table_name=false)
    {
        return R\Lib\Table\TableFactory::getInstance($table_name);
    }
    /**
     * @facade R\Lib\Route\RouteManager::getInstance
     */
    function route ($route_name=false)
    {
        return R\Lib\Route\RouteManager::getInstance($route_name);
    }
    /**
     * @facade R\Lib\Route\RouteManager->getWebroot
     */
    function webroot ($webroot_name=false)
    {
        return route()->getWebroot($webroot_name);
    }
    /**
     * @facade R\Lib\Form\FormFactory::getInstance
     */
    function form ()
    {
        return R\Lib\Form\FormFactory::getInstance();
    }
    /**
     * @facade R\Lib\Enum\EnumFactory::getInstance
     */
    function enum ($enum_set_name=false, $group=false)
    {
        return R\Lib\Enum\EnumFactory::getInstance($enum_set_name, $group);
    }
    /**
     * @facade R\Lib\Enum\Enum_Base::offsetGet
     */
    function enum_select ($value, $enum_set_name=false, $group=false)
    {
        return enum($enum_set_name, $group)->offsetGet($value);
    }
    /**
     * @facade R\Lib\Asset\AssetManager::getInstance
     */
    function asset () {
        return R\Lib\Asset\AssetManager::getInstance();
    }
    /**
     * @facade R\Lib\FileStorage\FileStorageManager::getInstance
     */
    function file_storage ()
    {
        return R\Lib\FileStorage\FileStorageManager::getInstance();
    }
    /**
     * @facade R\Lib\Builder\WebappBuilder::getInstance
     */
    function builder ()
    {
        return R\Lib\Builder\WebappBuilder::getInstance();
    }
    /**
     * @facade R\Lib\Core\UtilProxyManager::getProxy
     */
    function util ($class_name, $constructor_args=false)
    {
        return R\Lib\Core\UtilProxyManager::getProxy($class_name,$constructor_args);
    }
    /**
     * @facade R\Lib\Core\ExtentionManager::getExtention
     */
    function extention ($extention_group, $extention_name)
    {
        return R\Lib\Core\ExtentionManager::getExtention($extention_group, $extention_name);
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
    function array_set ( & $ref, $key, $value=null)
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
                array_set($ref, $k, $v);
            }
            return;
        // valueを配列で指定した場合
        } elseif (is_array($value)) {
            $ref_sub = & array_get_ref($ref, $key);
            foreach ($value as $k => $v) {
                array_set($ref_sub, $k, $v);
            }
            return;
        }
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

// -- 移行する

    /**
     * @deprecated
     */
    function start_dync () {
        if (app()->isDevClient()) {
            if (isset($_POST["__ts"]) && isset($_POST["_"])) {
                for ($min = floor(time()/60), $i=-5; $i<=5; $i++) {
                    if ($_POST["__ts"] == substr(md5("_/".($min+$i)),12,12)) {
                        $_SESSION["__debug"] = $_POST["_"]["report"];
                    }
                }
            }
            app()->config(array("Config.debug_level"=>$_SESSION["__debug"]));
            if (app()->getDebugLevel() && $_POST["__rdoc"]["entry"]==="build_rapp") {
                builder()->start();
            }
        }
    }
    /**
     *
     */
    function str_camelize ($str) {

        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }

    //-------------------------------------
    // URLの組み立て
    function url ($base_url=null, $params=array(), $anchor=null) {

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
