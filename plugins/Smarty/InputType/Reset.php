<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

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

        $this->html =Html::tag("input",$attrs);
        $this->assign =array();
    }
}