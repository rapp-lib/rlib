<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Number extends BaseRule {

	/**
	 * override
	 */
	protected $message ="数字のみで入力してください";

	/**
	 * override
	 */
	public function check ($value) {

		return  ! strlen($value) || ctype_digit($value);
	}
}