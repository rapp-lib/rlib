<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 * 
 */
class Textarea extends BaseInput
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
		
		foreach($attrs as $k => $v ){
			$attr_html .=' '.$k.'="'.str_replace('"','&quot;',$v).'"';
		}
		
		$html ='';
		$html .='<textarea'.$attr_html;
		$html .='>'.$value.'</textarea>';
		
		$this->html =$html;
		$this->assign =array();
	}
}