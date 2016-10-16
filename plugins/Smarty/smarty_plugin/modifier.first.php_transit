<?php

    //-------------------------------------
    // 最初の要素を取得する
    function smarty_modifier_first ($array) {

        if (is_string($array)) {

            $array =unserialize($array);
        }

         return $array && is_array($array)
                ? array_first($array)
                : null;
    }