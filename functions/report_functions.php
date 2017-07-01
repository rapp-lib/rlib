<?php

// -- レポートドライバ

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
    function report_buffer_start ()
    {
        app()->report->bufferStart();
    }
    function report_buffer_end ($all=false)
    {
        app()->report->bufferEnd($all);
    }
