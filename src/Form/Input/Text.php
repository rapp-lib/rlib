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
		$attrs["name"] =$name;
		$attrs["value"] =$value;
		$attrs["type"] ="text";
		
		$this->html =Html::tag("input",$attrs);
		$this->assign =array();
	}
}