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
	public function __construct ($value, $attrs) 
	{
		list($params,$attrs) =$this->filterAttrs($attrs,array(
			"type",
			"value",
		));

		if (isset($value)) {
			
			$params["value"] =$value;
		}
		
		$attr_html ="";

		foreach($attrs as $k => $v ){
			
			$attr_html .=' '.$k.'="'.str_replace('"','&quot;',$v).'"';
		}
		
		$html ='';
		$html .='<textarea'.$attr_html;
		$html .='>'.$params["value"].'</textarea>';
		
		$this->html =$html;
		$this->assign =array();
	}
}