<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Alphabet extends BaseRule {

	/**
	 * override
	 */
	protected $message ="英字のみで入力してください";

	/**
	 * override
	 */
	public function check ($value) {

		return  ! strlen($value) || ctype_alpha($value);
	}
}