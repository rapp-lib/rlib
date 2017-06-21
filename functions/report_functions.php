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
        app()->error->raise($message, $vars, $options);
    }

// -- レポートバッファ制御

    function report_buffer_start ()
    {
        $GLOBALS["__REPORT_BUFFER_LEVEL"] += 1;
    }
    function report_buffer_end ($all=false)
    {
        // 全件終了
        if ($all) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] = 1;
        }
        // 開始していなければ処理を行わない
        if ($GLOBALS["__REPORT_BUFFER_LEVEL"] > 0) {
            $GLOBALS["__REPORT_BUFFER_LEVEL"] -= 1;
            if ($GLOBALS["__REPORT_BUFFER_LEVEL"] == 0) {
                print $GLOBALS["__REPORT_BUFFER"];
                $GLOBALS["__REPORT_BUFFER"] = "";
            }
        }
    }
