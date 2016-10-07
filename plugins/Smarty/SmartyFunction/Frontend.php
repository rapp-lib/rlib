<?php
namespace R\Plugin\Smarty\SmartyFunction;

/**
 *
 */
class Frontend
{
    /**
     * @overload
     *
     * {{frontend required="jquery:2.*"}}
     * {{frontend required="app-css" buffer="css"}}
     * {{frontend loaded="jquery:2.*"}}
     * {{frontend flush=true}}
     */
    public static function smarty_function_frontend ($params, $smarty_template)
    {
        $html = "";

        // state指定
        if ($state = $params["state"]) {
            frontend()->setState($state);
        }
        // required指定
        if ($required = $params["required"]) {
            // 登録先のBufferを指定
            $buffer_name = $params["buffer"];
            if ( ! $buffer_name) {
                $buffer_name = "default";
            }
            // 複数指定に対応
            if ( ! is_array($required)) {
                $required = array($required);
            }
            foreach ($required as $required_str) {
                list($module_name, $version_required) = explode(":",$required_str);
                frontend()->required($module_name, $version_required, $buffer_name);
            }
        }
        // loaded指定
        if ($loaded = $params["loaded"]) {
            // 複数指定に対応
            if ( ! is_array($loaded)) {
                $loaded = array($loaded);
            }
            foreach ($loaded as $loaded_str) {
                list($module_name, $version) = explode(":",$loaded_str);
                frontend()->loaded($module_name, $version);
            }
        }
        // flush指定
        if ($flush = $params["flush"]) {
            // 複数指定に対応
            if ( ! is_array($flush)) {
                $flush = array($flush);
            }
            foreach ($flush as $buffer_name) {
                if ($buffer_name === true) {
                    $buffer_name = "default";
                }
                $html .= frontend()->flush($buffer_name);
            }
        }

        return $html;
    }
}