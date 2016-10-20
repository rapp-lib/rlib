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
    public static function smarty_block ($attrs, $content, $smarty_template, $repeat)
    {
        // @depreaced form未対応のフォームの作成
        if ( ! $attrs["form"]) {
            SmartyBlockA::linkageBlock($attrs, $content, $smarty_template, $repeat);
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