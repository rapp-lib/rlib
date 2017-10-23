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
    public static function callback ($params, $smarty_template)
    {
        // FormContainerによるタグ生成
        $attrs = $params;
        if ($form = $attrs["form"] ? $attrs["form"] : $smarty_template->getCurrentForm()) {
            unset($attrs["form"]);
            $input_field = $form->getInputField($attrs["name"], $attrs);
            // assignが指定されている場合、分解したHTMLを変数としてアサイン
            if ($attrs["assign"]) {
                $smarty_template->assign($params["assign"], $input_field);
            } else {
                return $input_field->getHtml();
            }
        }
    }
}