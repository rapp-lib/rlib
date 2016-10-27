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
     * @facade R\Lib\Webapp\Request::getInstance
     */
    function request ()
    {
        return R\Lib\Webapp\Request::getInstance();
    }

    /**
     * @facade R\Lib\Auth\AccountManager::getInstance
     */
    function auth ($name=null)
    {
        return R\Lib\Auth\AccountManager::getInstance($name);
    }

    /**
     * @facade R\Lib\Table\TableFactory::getInstance
     */
    function table ($table_name=null)
    {
        return R\Lib\Table\TableFactory::getInstance($table_name);
    }

    /**
     * @facade R\Lib\Route\RouteManager::getInstance
     */
    function route ()
    {
        return R\Lib\Route\RouteManager::getInstance();
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
    function enum ($enum_set_name=null, $group=null)
    {
        return R\Lib\Enum\EnumFactory::getInstance($enum_set_name, $group);
    }

    /**
     * @facade R\Lib\Frontend\FrontendAssetManager::getInstance
     */
    function asset () {
        return R\Lib\Frontend\FrontendAssetManager::getInstance();
    }

    /**
     * @facade R\Lib\FileStorage\FileStorageManager::getInstance
     */
    function file_storage ()
    {
        return R\Lib\FileStorage\FileStorageManager::getInstance();
    }

    /**
     * @facade R\Lib\Builder\WebappBuilder::getSchema
     */
    function builder ()
    {
        return R\Lib\Builder\WebappBuilder::getSchema();
    }

    /**
     * @facade R\Lib\Core\UtilProxyManager::getProxy
     */
    function util ($class_name, $singleton=false)
    {
        return R\Lib\Core\UtilProxyManager::getProxy($class_name,$singleton);
    }

    /**
     * @facade R\Lib\Core\ExtentionManager::getExtention
     */
    function extention ($extention_group=null, $extention_name=null)
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