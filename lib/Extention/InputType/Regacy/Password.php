<?php
namespace R\Lib\Extention\InputType\Regacy;

/**
 *
 */
class Password extends BaseInput
{
    /**
     * @override
     */
    public function __construct ($value, $attrs)
    {
        list($params,$attrs) =$this->filterAttrs($attrs,array(
        ));

        if (isset($value)) {

            $attrs["value"] =$value;
        }

        if ($attrs["autocomplete"] != "on") {

            $attrs["autocomplete"] ="off";
        }

        $this->html =tag("input",$attrs);
        $this->assign =array();
    }
}