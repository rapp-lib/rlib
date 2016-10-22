<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyModifierDate
{
    /**
     * @overload
     */
    function callback ($string ,$format="Y/m/d" )
    {
        // 無効な日付であれば値を返さない
        if ( ! $string) {

            return "";
        }

        return longdate_format($string,$format);
    }
}
