<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 * 
 */
class Text extends BaseInput
{
	/**
	 * @override
	 */
	public function __construct ($name, $value, $attrs) 
	{
		list($params,$attrs) =$this->filterAttrs($attrs,array(
		));
		$attrs["type"] ="text";
		$attrs["value"] =$value;
		
		$this->html =Html::tag("input",$attrs);
		$this->assign =array();
	}
}