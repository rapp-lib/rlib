<?php
namespace R\Lib\Extention\InputType\Regacy;

/**
 *
 */
class Radio extends BaseInput
{
    /**
     * @override
     */
    public function __construct ($value, $attrs)
    {

        list($params,$attrs) =$this->filterAttrs($attrs,array(
            "checked",
        ));

        if ((isset($value) && $attrs['value'] == $value)
                || ( ! isset($value) && $params["checked"] )) {

            $attrs['checked'] ="checked";
        }

        $this->html =tag("input",$attrs);
        $this->assign =array();
    }
}