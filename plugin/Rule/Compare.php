<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Compare extends BaseRule {

	/**
	 * override
	 */
	protected $message ="値が不正です";

	/**
	 * override
	 */
	public function check ($value) {

		$op =$this->params["option"]["op"];
		$target =$this->params["option"]["target"];
		$type =$this->params["option"]["type"];
		
		if ( ! strlen($value) || ! strlen($target)) {
			
			return true;
		}

		if ($type == "date") {
			
			$value =strtotime($value);
			$target =strtotime($target);
		}
		
		return ($op == "<" && $value < $target) 
				|| ($op == ">" && $value > $target) 
				|| ($op == "<=" && $value <= $target) 
				|| ($op == ">=" && $value >= $target);
	}
}