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
    function callback ($string ,$format="Y/m/d") {
        return isset($string) ? longdate_format($string,$format) : "";
    }
}
