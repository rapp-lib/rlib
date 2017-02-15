<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyBlockCode
{
    /**
     * @overload
     *
     * 形式とバッファを指定してバッファに書き込む
     * {{code type="js"}}
     *     alert("test");
     * {{/code}}
     * {{code type="css" buffer="html"}}
     *     body { background-color: red; }
     * {{/code}}
     */
    public static function callback ($params, $content, $smarty_template, $repeat)
    {
        $html = "";
        // 開始タグの場合処理を行わない
        if ($repeat) {
            return;
        }
        // type指定（必須）
        $type = $params["type"];
        // buffer指定
        $buffer = $params["buffer"];
        if ($type=="js") {
            $type = "js_code";
            if ( ! isset($buffer)) {
                $buffer = "script";
            }
        } elseif ($type=="css") {
            $type = "css_code";
            if ( ! isset($buffer)) {
                $buffer = "css";
            }
        }
        // Bufferへの登録
        $resource = app()->asset->buffer($content, $type, $buffer);
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
                $resource->required($required_item, $buffer_name);
            }
        }
        return;
    }
}