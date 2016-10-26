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
        return longdate_format($string,$format);
    }
}
