<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Integer extends BaseRule {

	/**
	 * override
	 */
	protected $message ="整数で入力してください";

	/**
	 * override
	 */
	public function check ($value) {

		return  ! strlen($value) || ctype_digit(preg_replace('!^-!','',$value));
	}
}