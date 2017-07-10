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

// -- Webroot

    /**
     * 転送リクエストの作成
     */
    function redirect ($url, $params=array(), $anchor=null)
    {
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

// -- Report

    function report ($message, $vars=array())
    {
        if ( ! is_array($vars)) {
            $vars = array("value" => $vars);
        }
        if ( ! is_string($message)) {
            $vars["message"] = $message;
            $message = "DEBUG";
        }
        app()->report->getLogger()->info($message, $vars);
    }
    function report_warning ($message, $vars=array(), $options=array())
    {
        app()->report->getLogger()->warn($message, $vars);
    }
    function report_error ($message, $vars=array())
    {
        app()->report->raiseError($message, $vars);
    }
