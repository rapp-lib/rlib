<?php

    /**
     * [smarty_modifier_wp_call description]
     */
    function smarty_modifier_wp_call ()
    {
        WordpressAdapter::wp_load();
        $args =func_get_args();
        $func =array_shift($args);
        $result =call_user_func_array($func, $args);
        return $result;
    }
