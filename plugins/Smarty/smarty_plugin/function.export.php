<?php

    //-------------------------------------
    // {{export vars=["var_name"] scope="parent"}}のサポート
    function smarty_function_export ($params, $smarty_template) {

        $vars =(array)$params["vars"];
        $scope =$params["scope"];

        if ($scope == "parent") {

            $smarty_template;
        }
    }

