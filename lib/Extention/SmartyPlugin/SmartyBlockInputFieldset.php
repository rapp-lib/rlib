<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 * {{input_fieldset}}
 */
class SmartyBlockInputFieldset
{
    /**
     * @overload
     */
    public static function callback ($attrs, $content, $smarty, &$repeat)
    {
        $fieldset_name = $attrs["name"];
        $tmpl = $attrs["tmpl"] ?: null; // tmplに登録する際のkeyの値
        $key_assign = $attrs["key"] ?: "key"; // keyをアサインする変数名
        $assign = $attrs["assign"] ?: "fieldset"; // 結果をアサインする変数名
        $length = $attrs["length"] ?: null; // 固定数表示の場合の件数指定
        // Blockタグスタック上の情報を参照
        $tag = & $smarty->smarty->_cache['_tag_stack'][end($keys = array_keys($smarty->smarty->_cache['_tag_stack']))];
        // 初回の開くタグの処理
        if ($repeat===true) {
            // Keysの初期化
            $form = $smarty->getCurrentForm();
            $tag["keys"] = array();
            if ($length) $tag["keys"] = range(0,$length-1);
            else $tag["keys"] = isset($form[$fieldset_name]) ? array_keys((array)$form[$fieldset_name]) : array();
            // テンプレート処理用の要素をアサイン
            if (strlen($tmpl)) {
                $tag["current"] = "tmpl";
                $smarty->assign($key_assign, $tmpl);
            } else {
                $repeat = false;
            }
        }
        // 閉じタグ兼2周目以降の開くタグの処理
        if ($repeat===false) {
            // 処理した要素の出力をAssignに追加
            if ($tag["current"] === "tmpl") {
                $tag["assign"]["tmpl"] = $content;
            } elseif (strlen($tag["current"])) {
                $tag["assign"]["items"][$tag["current"]] = $content;
            }
            // Keysの残りがある限りループ処理
            if (count($tag["keys"])) {
                $smarty->assign($key_assign, $tag["current"] = array_shift($tag["keys"]));
                $repeat = true;
            // 全てのループ完了時にassignをおこなう
            } else {
                $smarty->assign($assign, $tag["assign"]);
            }
        }
    }
}
