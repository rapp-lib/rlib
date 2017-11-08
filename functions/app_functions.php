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
    function __ ($key, $values=array(), $locale=null)
    {
        return app()->i18n->getMessage($key, $values, $locale);
    }

// -- Webroot

    /**
     * 転送リクエストの作成
     * @deprecated
     */
    function redirect ($url, $params=array(), $anchor=null)
    {
        $uri = app()->http->getServedRequest()->getUri()->getWebroot()->uri($url, $params, $anchor);
        return app()->http->response("redirect", "".$uri);
    }
    /**
     * URLの組み立て
     */
    function url ($base_url=null, $params=array(), $anchor=null)
    {
        return app()->http->getServedRequest()->getUri()->getWebroot()->uri($base_url, $params, $anchor);
    }

// -- Report

    function report ()
    {
        $values = array();
        foreach (func_get_args() as $k=>$v) $values["arg-".$k] = $v;
        app()->report->getLogger()->debug("DEBUG", $values);
    }
    function report_info ($message, array $vars=array())
    {
        app()->report->getLogger()->info($message, $vars);
    }
    function report_warning ($message, array $vars=array())
    {
        app()->report->getLogger()->warn($message, $vars);
    }
    function report_error ($message, array $vars=array())
    {
        app()->report->raiseError($message, $vars);
    }

// -- Util

    function csv_open ($filename, $mode, $options=array())
    {
        return new \R\Lib\Util\CSVHandler($filename, $mode, $options);
    }
    function send_mail ($template_filename, $vars=array())
    {
        $mailer = new \R\Lib\Util\MailHandler();
        return $mailer->load($template_filename, $vars)->send();
    }
    function tag ($name, $attrs=null, $content=null)
    {
        return \R\Lib\Util\HtmlBuilder::build($name, $attrs, $content);
    }

