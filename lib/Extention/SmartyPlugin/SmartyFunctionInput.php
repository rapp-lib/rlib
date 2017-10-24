<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 * {{input ...}}
 */
class SmartyFunctionInput
{
    /**
     * @overload
     */
    public static function callback ($attrs, $smarty)
    {
        // FormContainerによるタグ生成
        $form = $attrs["form"];
        $assign = $attrs["assign"];
        unset($attrs["form"], $attrs["assign"]);
        if ( ! $form) foreach ($smarty->smarty->_cache['_tag_stack'] as $block) {
            if ($block[0] === "form" && $block[1]["form"]) $form = $block[1]["form"];
        }
        if ( ! $form) {
            report_error("{{input}}は{{form}}内でのみ有効です", array("attrs"=>$attrs));
        }
        $input_field = $form->getInputField($attrs);
        if ($assign) {
            // assignが指定されている場合、分解したHTMLを変数としてアサイン
            $smarty->assign($assign, $input_field);
        } else {
            return $input_field->getHtml();
        }
    }
}