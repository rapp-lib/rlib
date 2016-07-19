<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Required extends BaseRule {

	/**
	 * override
	 */
	protected $message ="必ず入力してください";

	/**
	 * override
	 */
	public function check ($value) {

		if (is_array($value)) {

			$value =implode('',$value);
		}
		
		return strlen($value)!==0;
	}
}