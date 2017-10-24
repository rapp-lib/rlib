<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 * @plugin Smarty\SmartyPlugin
 * {{form ...}}{{/form}}タグの実装
 */
class SmartyBlockForm
{
    /**
     * @overload
     */
    public static function callback ($attrs, $content, $smarty_template, $repeat)
    {
        $form = $attrs["form"];
        unset($attrs["form"]);
        if ( ! $form) report_error("formの指定は必須です");
        // 閉タグでHTML出力
        if ( ! $repeat) return $form->getFormHtml($attrs, $content);
    }
}
