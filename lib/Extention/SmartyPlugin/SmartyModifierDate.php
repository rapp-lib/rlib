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
        if ( ! strlen($string)) {
            return "";
        }
        $date = new \DateTime($string);
        return $date->format($format);
    }
}
