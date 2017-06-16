<?php

// -- レポートドライバ

    function report ($message, $vars=array(), $options=array())
    {
        $vars = array_merge($vars, $options);
        app()->log->info($message, $vars);
    }
    function report_warning ($message, $vars=array(), $options=array())
    {
        $vars = array_merge($vars, $options);
        app()->log->warn($message, $vars);
    }
    function report_error ($message, $vars=array(), $options=array())
    {
        $vars = array_merge($vars, $options);
        app()->log->err($message, $vars);
    }

// -- レポートバッファ制御

    function report_buffer_start ()
    {
        app()->log->report_buffer_start();
    }
    function report_buffer_end ($all=false)
    {
        app()->log->report_buffer_end($all);
    }
