<?php
namespace R\Plugin\Smarty\SmartyPlugin;

/**
 * @plugin Smarty\SmartyPlugin
 * {{fieldset name="imgs"}}{{/fieldset}}タグの実装
 */
class SmartyBlockFieldset
{
    /**
     * @overload
     */
    public static function smarty_block ($params, $content, $smarty_template, $repeat)
    {
        // FormContainerの登録
        if ($form = $params["form"] ? $params["form"] : $smarty_template->getCurrentForm()) {
            // 開タグ
            if ($repeat) {
                $input_fieldset = $form->getInputFieldsetByNameAttr($params["name"]);
                $smarty_template->setCurrentFieldset($input_fieldset);
            // 閉タグ
            } else {
                $smarty_template->removeCurrentFieldset();
            }
        }
    }
}