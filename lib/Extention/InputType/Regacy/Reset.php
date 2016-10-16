<?php
namespace R\Lib\Extention\InputType\Regacy;

/**
 *
 */
class Reset extends BaseInput
{
    /**
     * @override
     */
    public function __construct ($value, $attrs)
    {
        list($params,$attrs) =$this->filterAttrs($attrs,array(
        ));

        $this->html =tag("input",$attrs);
        $this->assign =array();
    }
}