<?php

    function smarty_modifier_concat ($array, $delim) {

        return is_array($array)
                ? implode($array,$delim)
                : $array;
    }
