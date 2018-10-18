<?php

    define("R_LIB_ROOT_DIR", realpath(__DIR__."/.."));

    if ( ! defined("R_DEVEL_ROOT_DIR") && $devel_root_dir=realpath(__DIR__."/../../rlib-devel")){
        define("R_DEVEL_ROOT_DIR", $devel_root_dir);
    }

// -- Container Facade

    function table ($table_name)
    {
        return app()->table($table_name);
    }
    function ___ ($key, $values=array(), $locale=null)
    {
        return app("i18n")->getMessage($key, $values, $locale);
    }
    function report ()
    {
        $vars = array();
        foreach (func_get_args() as $k=>$v) $vars["value #".$k] = $v;
        $vars["__"]["category"] = "Debug";
        app("log")->write("debug", "DEBUG", $vars);
    }
    function report_info ($message, array $vars=array(), $category=null)
    {
        $vars["__"]["category"] = strlen($category) ? $category : "Info";
        app("log")->write("info", $message, $vars);
    }
    function report_warning ($message, array $vars=array(), $category=null)
    {
        $vars["__"]["category"] = strlen($category) ? $category : "Warning";
        app("log")->write("warning", $message, $vars);
    }
    function report_error ($message, array $vars=array())
    {
        throw \R\Lib\Report\ReportRenderer::createHandlableError(array(
            "message"=>$message,
            "params"=>$vars,
        ));
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

// -- String Util

    function str_camelize ($str)
    {
        return str_replace(' ','',ucwords(str_replace('_', ' ', $str)));
    }
    function str_underscore ($str)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }
    function str_date ($string, $format="Y/m/d")
    {
        if ( ! strlen($string)) return "";
        if (is_numeric($string)) $string = date("Y/m/d H:i:s", (int)$string);
        try {
            $date = new \DateTime($string);
        } catch (\Exception $e) {
            return null;
        }
        // 日本語の曜日 x
        if (preg_match('/x/', $format)) {
            $w = $date->format("w");
            $week_jp = array(0 => '日', 1 => '月', 2 => '火',
                3 => '水', 4 => '木', 5 => '金', 6 => '土');
            $format = preg_replace('/x/', $week_jp[$w], $format);
        }
        return $date->format($format);
    }
    function rand_string ($length=8, $seed=null)
    {
        $charmap = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars = str_split($charmap);
        $string = "";
        if (isset($seed)) srand(crc32((string)$seed));
        for ($i=0; $i<$length; $i++) $string .=$chars[array_rand($chars)];
        return $string;
    }
