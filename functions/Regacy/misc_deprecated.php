<?php

// -- 削除予定

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
