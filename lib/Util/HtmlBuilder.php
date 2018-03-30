<?php
namespace R\Lib\Util;

use HtmlGenerator\HtmlTag;

/**
 * HTMLタグ組み立てクラス
 */
class HtmlBuilder extends HtmlTag
{
    public static $outputLanguage  = ENT_HTML401;
    public static $avoidXSS  = true;
    public static function build($name, $attrs=null, $content=null)
    {
        $elm = is_string($name) ? self::createElement($name) : $name;
        if ($attrs) foreach ($attrs as $k=>$v) {
            if ($k=="class" && is_array($v)) foreach ($v as $cls) $elm->addClass($cls);
            elseif (is_numeric($k)) $elm->set($v);
            else $elm->set($k, $v);
        }
        if (is_array($content)) foreach ($content as $sub) tag($elm->addElement($sub[0]),$sub[1],$sub[2]);
        elseif($content instanceof \HtmlGenerator\HtmlTag) $elm->addElement()->text = $content;
        elseif (is_string($content)) $elm->addElement()->text = $content;
        return $elm;
    }
}
