<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 * 
 */
class Password extends BaseInput
{
	/**
	 * @override
	 */
	public function __construct ($name, $value, $attrs) 
	{
		list($params,$attrs) =$this->filterAttrs($attrs,array(
		));
		$attrs["type"] ="text";
		$attrs["name"] =$name;
		$attrs["value"] =$value;

		if ($attrs["autocomplete"] != "on") {
		
			$attrs["autocomplete"] ="off";
		}
		
		$this->html =Html::tag("password",$attrs);
		$this->assign =array();
	}
}