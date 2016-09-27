<?php

    function smarty_modifier_hidetext ($text) {

        return preg_replace('!.!','*',$text);
    }
