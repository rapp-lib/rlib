<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierWpCall
{
    /**
     * @overload
     */
    function callback ()
    {
        WordpressAdapter::wp_load();
        $args =func_get_args();
        $func =array_shift($args);
        $result =call_user_func_array($func, $args);
        return $result;
    }
}