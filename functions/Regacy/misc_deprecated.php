<?php

// -- 削除

    /**
     * PageからURLを得る（主にRedirectやHREFに使用）
     */
    function page_to_url_DELETE ($page, $url_params=array(), $anchor=null)
    {
        if (preg_match('!^\.(.*)$!', $page, $match)) {
            $request_page_id = app()->http->getServedRequest()->getUri()->getPageId();
            $part = explode(".", $request_page_id, 2);
            $page = $part[0].".".($match[1] ?: $part[1]);
        }
        $uri = app()->http->getWebroot()->uri("id://".$page, $url_params, $anchor)->getAbsUriString();
        return "".$uri;
    }
    /**
     * PathからURLを得る（主にRedirectやHREFに使用）
     */
    function path_to_url_DELETE ($path, $url_params=array(), $anchor=null)
    {
        $uri = app()->http->getWebroot()->uri("path://".$path, $url_params, $anchor)->getAbsUriString();
        return "".$uri;
    }

// -- 削除予定

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
     * @deprecated
     */
    function registry ($name, $value=null)
    {
        if (isset($value)) {
            report_warning("@deprecated registry assign");
            app()->config(array($name=>$value));
            return;
        }
        return app()->config($name);
    }
    /**
     * @deprecated
     * ドット記法で配列の値を設定する
     */
    function array_set ( & $ref, $key, $value)
    {
        report_warning("@deprecated array_set");
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
     * @deprecated
     */
    function builder ()
    {
        report_warning("@deprecated builder");
        return app()->builder;
    }
    /**
     * @deprecated
     */
    function form ()
    {
        report_warning("@deprecated form");
        return app()->form;
    }
    /**
     * @deprecated
     */
    function enum ($enum_set_name=false, $group=false)
    {
        report_warning("@deprecated enum");
        return app()->enum($enum_set_name, $group);
    }
    /**
     * @deprecated
     */
    function enum_select ($value, $enum_set_name=false, $group=false)
    {
        report_warning("@deprecated enum_select");
        return app()->enum_select($value, $enum_set_name, $group);
    }
    /**
     * @deprecated
     */
    function asset () {
        report_warning("@deprecated asset");
        return app()->asset;
    }
    /**
     * @deprecated
     */
    function file_storage ()
    {
        report_warning("@deprecated file_storage");
        return app()->file_storage;
    }
