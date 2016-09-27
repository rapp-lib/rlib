<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 *
 */
class Checkbox extends BaseInput
{
    /**
     * @override
     */
    public function __construct ($value, $attrs)
    {

        list($params,$attrs) =$this->filterAttrs($attrs,array(
        ));

        if ((strlen($value) && $attrs["value"] == $value)
                || ( ! strlen($value) && $attrs["checked"])) {

            $attrs['checked'] ="checked";

        } else {

            unset($attrs['checked']);
        }

        $attr_html ="";

        foreach ($attrs as $k => $v) {

            $attr_html .=' '.$k.'="'.$v.'"';
        }

        $html ='';
        $html .=(
            '<input'
            .' type="hidden"'
            .' name="'.$attrs["name"].'"'
            .' value=""'
            .' />'."\n"
        );
        $html .=(
            '<input'
            .' type="checkbox"'
            .$attr_html
            .' />'
        );

        $this->html =$html;
        $this->assign =array();
    }
}