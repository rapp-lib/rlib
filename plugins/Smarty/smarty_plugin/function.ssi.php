<?php

    //-------------------------------------
    // {{ssi virtual="/element/head.html"}}のサポート
    function smarty_function_ssi ($params, $smarty_template) {

        $virtual =$params["virtual"];

        return virtual($virtual);
    }

