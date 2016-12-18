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
     * @facade registry
     */
    function config ($key)
    {
        $ref = & $GLOBALS["__REGISTRY__"];
        if (is_array($key)) {
            foreach ($key as $k=>$v) {
                array_add($ref, $k, $v);
            }
        } elseif (is_string($key)) {
            return array_get($ref, $key);
        }
    }

    /**
     * @facade R\Lib\Webapp\Request::getInstance
     */
    function request ()
    {
        return R\Lib\Webapp\Request::getInstance();
    }

    /**
     * @facade R\Lib\Webapp\Response::getInstance
     */
    function response ()
    {
        return R\Lib\Webapp\Response::getInstance();
    }

    /**
     * @facade R\Lib\Webapp\Session::getInstance
     */
    function session ($key)
    {
        return R\Lib\Webapp\Session::getInstance($key);
    }

    /**
     * @facade R\Lib\Auth\AccountManager::getInstance
     */
    function auth ($name=false)
    {
        return R\Lib\Auth\AccountManager::getInstance($name);
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
        if ($dync_key = config("Config.dync_key")) {
            $dync =(array)$_SESSION["__dync"];
            $sec =$_REQUEST["__ts"];
            $min =floor(time()/60);
            $sec_list =array();
            foreach (range(-5,5) as $i) {
                $sec_list[$i] =(substr(md5($dync_key."/".($min+$i)),12,12));
            }
            if ($_REQUEST[$dync_key] && $sec && (in_array($sec, $sec_list))) {
                $_SESSION["__dync"] = array_merge((array)$_SESSION["__dync"],(array)$_REQUEST[$dync_key]);
            }
            registry("Config.dync", $_SESSION["__dync"]);
            if (app()->getDebugLevel() && $_POST["__rdoc"]["entry"]=="build_rapp") {
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
