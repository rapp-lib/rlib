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
     * @alias
     */
    function auth ($role_name=false)
    {
        return app()->auth($role_name);
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
