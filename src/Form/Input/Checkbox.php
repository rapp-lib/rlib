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
	public function __construct ($name, $value, $attrs) 
	{
		
		list($params,$attrs) =$this->filterAttrs($attrs,array(
		));
		$attrs["name"] =$name;

		$attr_html ="";
		
		foreach ($attrs as $k => $v) {
			$attr_html .=' '.$k.'="'.$v.'"';
		}
		
		$html ='';
		$html .=(
			'<input'
			.' type="hidden"'
			.' name="'.$name.'"'
			.' value=""'
			.' />'."\n"
		);
		$html .=(
			'<input'
			.' type="checkbox"'
			.' value="'.$value.'"'
			.$attr_html
			.' />'
		);

		$this->html =$html;
		$this->assign =array();
	}
}