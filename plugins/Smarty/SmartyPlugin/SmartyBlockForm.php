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
        return SmartyBlockA::linkageBlock("form", $params, $content, $template, $repeat);
    }
}