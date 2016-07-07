<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Alphanum extends BaseRule {

	/**
	 * override
	 */
	protected $message ="半角英数字で入力してください";

	/**
	 * override
	 */
	public function check ($value, $params) {

		return  ! strlen($value) || ctype_alnum($value);
	}
}