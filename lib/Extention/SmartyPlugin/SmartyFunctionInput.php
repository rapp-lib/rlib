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
                $smarty->assign($params["assign"], $input_field);
                return;
            }
            return $input_field->getHtml();
        }

        static $input_index_counter =array();

        $type =& $params["type"];
        $name =& $params["name"];

        // name属性補完
        if ( ! strlen($params['name']) && ($type=='submit' || $type=='button'
                || $type=='image' || $type=='reset')) {

            $name ='button';
        }

        // 必須属性チェック
        if ( ! isset($type)) {

            report_warning("Input type is unspecified.");
            return "";

        } elseif ( ! isset($params['name'])) {

            report_warning("Input name is unspecified.");
            return "";
        }

        // []の解決
        if (strpos($name,"[]")!==false) {

            report_error("Invalid input-name include [].",$params);

            $input_index_key =preg_replace('!\[\].*$!','',$name);
            $input_index_value =++$input_index_counter[$input_index_key];

            $name =str_replace('[]','['.$input_index_value.']',$name);
        }

        // フォームの値
        $name_ref =$name;
        $name_ref =str_replace('][','.',$name_ref);
        $name_ref =str_replace('[','.',$name_ref);
        $name_ref =str_replace(']','',$name_ref);

        $postset_value =ref_array($smarty->vars,"input.".$name_ref);

        // Contextのinput内のデータ
        if ( ! $postset_value
                && preg_match('!^([^\[]+)\[([^\[]+)\]((\[[^\[]+\])*)$!',$name,$match)) {

            $context_name =$match[1];
            $name_ref_context =$match[2];
            $name_ref_complex =$match[3];

            if ($context =$smarty->vars[$context_name]) {

                $postset_value =$context->input($name_ref_context);

                // input要素内が配列構造
                if ($name_ref_complex) {

                    // Serializeされた文字列は展開
                    if (is_string($postset_value)) {

                        $postset_value =unserialize($postset_value);
                    }

                    // 参照可能であれば、下層のデータを抽出
                    if ($postset_value && is_array($postset_value)) {

                        $name_ref_complex =str_replace('.','..',$name_ref_complex);
                        $name_ref_complex =str_replace('][','.',$name_ref_complex);
                        $name_ref_complex =str_replace('[','',$name_ref_complex);
                        $name_ref_complex =str_replace(']','',$name_ref_complex);

                        $postset_value =ref_array($postset_value,$name_ref_complex);

                    } else {

                        $postset_value =null;
                    }
                }
            }
        }

        // formで指定された値
        if ($smarty->current_form) {
            $values =$smarty->current_form["values"];
            // Contextが指定されている
            if (is_object($values) && is_callable(array($values,"input"))) {
                $values =$values->input();
            }
            $postset_value =ref_array($values,$name_ref);
        }
        $value = $postset_value;

        // InputTypeExtentionを呼び出す
        $html= call_user_func_array(extention("InputType",$type),array($value,$params));
        // assign変数名が指定されている場合はHTMLを返さずAssignする
        if ($params["assign"]) {
            $smarty->assign($params["assign"], $html);
        } else {
            return $html["formatted"];
        }
    }
}