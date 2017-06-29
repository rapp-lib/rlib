<?php

// -- 各クラスのインスタンス取得

    /**
     *
     */
    function app_set ($container)
    {
        $GLOBALS["R_CONTAINER"] = $container;
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
        $uri = app()->http->getServedRequest()->getWebroot()->uri($url, $params, $anchor);
        return app()->http->response("redirect", "".$uri);
    }
    /**
     * URLの組み立て
     */
    function url ($base_url=null, $params=array(), $anchor=null)
    {
        return app()->http->getServedRequest()->getWebroot()->uri($base_url, $params, $anchor);
    }
