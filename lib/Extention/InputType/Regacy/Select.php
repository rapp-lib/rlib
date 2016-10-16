<?php
namespace R\Lib\Extention\InputType\Regacy;

/**
 *
 */
class Select extends BaseInput
{
    /**
     * select系のタグの構築（select/radioselect/checklistタグで共通）
     * @override
     */
    public function __construct ($value, $attrs)
    {
        $selected_value =isset($value)
                ? $value
                : $attrs["value"];

        list($params,$attrs) =$this->filterAttrs($attrs,array(
            "type",
            "id",
            "name",
            "assign", // 指定した名前で部品のアサイン

            "values", // 値リストの配列指定
            "options", // List名の指定
            "options_params", // List::optionsの引数
            "parent_id", // 連動対象の要素のID
            "parents_params", // List::parentsの引数

            // Checklist以外
            "zerooption", // 先頭の非選択要素の指定

            // Selectのみ
            "nozerooption", // 非選択要素を自動的に追加しない指定
            "optgroup_options", // OPTGROUPを生成するためのList名
            "optgroup_options_params", // OPTGROUPを生成するためのList::optionsの引数
        ));

        // HTML属性
        $attr_html ="";
        foreach ($attrs as $k => $v) {
            $attr_html .=' '.$k.'="'.$v.'"';
        }

        // id属性の補完
        $params["id"] =$params["id"]
                ? $params["id"]
                : sprintf("ELM%09d",mt_rand());

        // optionsの配列としてparamsを渡す
        if (is_array($params["options"])) {

            $list_name =array_shift($params["options"]);
            $params["options_params"] =$params["options"];
            $params["options"] =$list_name;
        }

        // valuesで配列によってリストを渡す
        if ( ! $params["options"] && $params["values"]) {

            $list_options =get_list(null,$this);
            $options =$params["values"];

        } else {

            $list_options =get_list($params["options"],$this);
            $options =$list_options->options($params["options_params"]);
        }

        // 空白選択の挿入(Checklist以外)
        if ($params["type"] != "checklist" && isset($params["zerooption"])) {

            $options =array("" =>$params["zerooption"]) + $options;

        // Select要素には空白要素を自動挿入
        } elseif ($params["type"] == "select"
                && ! isset($params["nozerooption"])) {

            $tmp_optioins =array("" =>"");

            foreach ($options as $k =>$v) {

                $tmp_optioins[$k] =$v;
            }

            $options =$tmp_optioins;
        }

        $html =array(
            "full" =>"",
            "head" =>"",
            "foot" =>"",
            "options" =>array(),
        );

        if ($params["type"] == "select") {

            $html["head"] ='<select id="'.$params["id"].'"'
                    .' name="'.$params["name"].'"'.$attr_html.'>'."\n";
            $html["foot"] ='</select>';

            foreach ($options as $option_value => $option_label) {

                $selected =(string)$option_value === (string)$selected_value;
                $html["options"][$option_value] ='<option'
                        .' value="'.$option_value.'"'
                        .($selected ? ' selected="selected"' : '')
                        .'>'.$option_label.'</option>'."\n";
                $html["split_options"][$option_value] =array(
                    "value" =>$option_value,
                    "selected" =>$selected,
                    "label" =>$option_label,
                );
            }

            // optgroupでまとめる
            if ($params["optgroup_options"]) {

                $rels =$list_options->parents($params["parents_params"]);
                $parent_list_options =get_list($params["optgroup_options"],$this);
                $parent_options =$parent_list_options->options($params["optgroup_options_params"]);

                $option_htmls =$html["options"];
                $html["options"] =array();

                if (isset($option_htmls[""])) {

                    $html["options"] =array("" =>$option_htmls[""]);
                }

                foreach ($parent_options as $parent_k => $parent_label) {

                    $html["options"][$parent_k] .='<optgroup label="'.$parent_label.'">';

                    foreach ($option_htmls as $option_k => $option_html) {

                        if ($rels[$option_k] == $parent_k) {

                            $html["options"][$parent_k] .=$option_html;
                        }
                    }

                    $html["options"][$parent_k] .='<optgroup>';
                }
            }

        } elseif ($params["type"] == "radioselect") {

            $html["head"] ='';
            $html["foot"] ='';

            foreach ($options as $option_value => $option_label) {

                $checked =(string)$option_value === (string)$selected_value;
                $html["options"][$option_value] =
                        '<nobr><label>'.'<input type="radio"'
                        .' name="'.$params["name"].'"'
                        .' value="'.$option_value.'"'.$attr_html
                        .($checked ? ' checked="checked"' : '')
                        .'> <span class="labeltext">'.$option_label
                        .'</span></label></nobr> &nbsp;'."\n";
                $html["split_options"][$option_value] =array(
                    "name" =>$params["name"],
                    "value" =>$option_value,
                    "checked" =>$checked,
                    "label" =>$option_label,
                );
            }

        } elseif ($params["type"] == "checklist") {

            if (is_string($selected_value)) {

                $selected_value =unserialize($selected_value);

            } elseif ( ! is_array($selected_value)) {

                $selected_value =(array)$selected_value;
            }

            $html["head"] ='<input type="hidden" name="'.$params['name'].'" value="" />'."\n";
            $html["foot"] ='';

            foreach ($options as $option_value => $option_label) {

                $checked =false;

                foreach ((array)$selected_value as $a_selected_value) {

                    if ((string)$option_value === (string)$a_selected_value) {

                        $checked =true;
                        break;
                    }
                }

                $html["options"][$option_value] =
                        '<nobr><label>'.'<input type="checkbox"'
                        .' name="'.$params["name"].'['.$option_value.']'.'"'
                        .' value="'.$option_value.'"'.$attr_html
                        .($checked ? ' checked="checked"' : '')
                        .'> <span class="labeltext">'.$option_label
                        .'</span></label></nobr> &nbsp;'."\n";
                $html["split_options"][$option_value] =array(
                    "name" =>$params['name'].'['.$option_value.']',
                    "value" =>$option_value,
                    "checked" =>$checked,
                    "label" =>$option_label,
                );
            }

        } elseif ($params["type"] == "multiselect") {

            if (is_string($selected_value)) {

                $selected_value =unserialize($selected_value);

            } elseif ( ! is_array($selected_value)) {

                $selected_value =(array)$selected_value;
            }

            $html["head"] ='<select id="'.$params["id"].'"'
                    .' name="'.$params["name"].'[]" multiple="multiple"'
                    .$attr_html.'>'."\n";
            $html["foot"] ='</select>';

            foreach ($options as $option_value => $option_label) {

                $selected =false;

                foreach ((array)$selected_value as $a_selected_value) {

                    if ((string)$option_value === (string)$a_selected_value) {

                        $selected =true;
                        break;
                    }
                }

                $html["options"][$option_value] ='<option'
                        .' value="'.$option_value.'"'
                        .($selected ? ' selected="selected"' : '')
                        .'>'.$option_label.'</option>'."\n";
                $html["split_options"][$option_value] =array(
                    "value" =>$option_value,
                    "selected" =>$selected,
                    "label" =>$option_label,
                );
            }

        }

        // 親要素との連動
        if ($params["parent_id"]) {

            // get_list_json.html?parent=xによる動的関連付け
            // ※現状ではtype="select"でのみ使用可能
            if ($params["parent_rel_url"]) {

                $pair ='"'.url($params["parent_rel_url"],array()).'"';

            // list->parents()による静的な関連付け
            } else {

                $parents =$list_options->parents($params["parents_params"]);
                $pair =json_encode($parents);

                if ($params["type"] == "radioselect" || $params["type"] == "checklist") {

                    foreach ($html["options"] as $k => $v) {

                        $html["options"][$k] ='<span class="_listitem">'.$v.'</span>';
                    }

                    $html["head"] ='<span id="'.$params["id"].'">'.$html["head"];
                    $html["foot"] =$html["foot"].'</span>';
                }
            }

            // Frontendライブラリ導入以前との互換処理
            if ( ! asset()->getRegisteredModule("rui.datefix")) {
                $html["foot"] .='<script>/*<!--*/ rui.require("rui.syncselect",function(){ '
                        .'rui.syncselect("'.$params['id'].'",'.'"'.$params['parent_id'].'",'
                        .$pair.',"'.$params["type"].'"); }); /*-->*/</script>';
            } else {
                // 親子Selectの連動JS処理
                asset()->bufferJsCode(array(
                    '$(function(){',
                    '   rui.syncselect(',
                    '       "'.$params['id'].'", "'.$params['parent_id'].'",',
                    '       '.$pair.',"'.$params["type"].'"',
                    '   );',
                    '});',
                ))->required("rui.syncselect");

            }
        }

        $html["full"] =$html["head"].implode("",$html["options"]).$html["foot"];

        $this->html =$html["full"];
        $this->assign =$html;
    }
}