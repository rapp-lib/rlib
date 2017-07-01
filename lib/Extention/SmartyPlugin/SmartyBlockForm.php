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
        // @depreaced form未対応のフォームの作成
        if ( ! $attrs["form"]) {
            report_error("formの指定は必須です");
        }
        $form = $attrs["form"];
        unset($attrs["form"]);
        // 開タグ
        if ($repeat) {
            $smarty_template->setCurrentForm($form);
        // 閉タグ
        } else {
            $smarty_template->removeCurrentForm();
            return $form->getFormHtml($attrs, $content);
        }
    }
}