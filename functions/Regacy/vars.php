<?php

    //-------------------------------------
    //
    function & ref_globals ($name) {
        report_warning("@deprecated ref_globals");

        $name ="__".strtoupper($name)."__";
        return $GLOBALS[$name];
    }

    //-------------------------------------
    //
    function & ref_session ($name) {
        report_warning("@deprecated ref_session");

        $name ="__".strtoupper($name)."__";
        return $_SESSION[$name];
    }