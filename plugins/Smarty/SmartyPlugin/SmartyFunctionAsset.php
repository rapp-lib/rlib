<?php
namespace R\Plugin\Smarty\SmartyPlugin;

/**
 *
 */
class SmartyFunctionAsset
{
    /**
     * @overload
     *
     * モジュールを指定してバッファに読み込む
     * {{asset required="jquery:2.*"}}
     * {{asset required="app.common-script" buffer="script"}}
     * {{asset required="app.common-css" buffer="css"}}
     *
     * バッファの出力
     * {{asset flush=["css","script"] state="head.end"}}
     * {{asset flush="*" state="body.end"}}
     *
     * モジュールがHTML上で直接読み込まれたことを通知
     * {{asset loaded="jquery:2.*"}}
     */
    public static function smarty_function ($params, $smarty_template)
    {
        $html = "";

        // state指定
        if ($state = $params["state"]) {
            asset()->setState($state);
        }
        // required指定
        if ($required = $params["required"]) {
            // 登録先のBufferを指定
            $buffer_name = $params["buffer"];
            if ( ! $buffer_name) {
                $buffer_name ="html";
            }
            // 複数指定に対応
            if ( ! is_array($required)) {
                $required = array($required);
            }
            foreach ($required as $required_item) {
                asset()->required($required_item, $buffer_name);
            }
        }
        // loaded指定
        if ($loaded = $params["loaded"]) {
            // 複数指定に対応
            if ( ! is_array($loaded)) {
                $loaded = array($loaded);
            }
            foreach ($loaded as $loaded_item) {
                asset()->loaded($loaded_item);
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
                $html .= asset()->flush($buffer_name);
            }
        }

        return $html;
    }
}