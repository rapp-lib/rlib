<?php
namespace R\Plugin\Smarty\SmartyPlugin;

/**
 * @plugin Smarty\SmartyPlugin
 * {{form ...}}{{/form}}タグの実装
 */
class SmartyBlockForm
{
    /**
     * @overload
     */
    public static function smarty_block ($params, $content, $smarty_template, $repeat)
    {
        // 開タグ
        if ($repeat) {
            if ($form = $params["form"]) {
                $smarty_template->setCurrentForm($form);
            }
        // 閉タグ
        } else {
            if ($form = $params["form"]) {
                $smarty_template->removeCurrentForm();
                // Received
                $content = $form->getReceiveParamHidden().$content;
            }

            // formタグを返す
            unset($params["form"]);
            $params["href"] =url($params["href"]);
            return tag("form",$params,$content);
        }
    }
}