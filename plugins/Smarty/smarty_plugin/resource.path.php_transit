<?php

    function smarty_resource_path_source ($tpl_name, &$tpl_source, $smarty) {

        $file =$smarty->resolve_resource_path($tpl_name,true);
        $tpl_source =file_get_contents($file);
        return true;
    }

    function smarty_resource_path_timestamp($tpl_name, &$tpl_timestamp, $smarty) {

        $file =$smarty->resolve_resource_path($tpl_name);
        $tpl_timestamp =filemtime($file);
        return true;
    }

    function smarty_resource_path_secure ($tpl_name, $smarty) {

        return true;
    }

    function smarty_resource_path_trusted ($tpl_name, $smarty) {

        return true;
    }