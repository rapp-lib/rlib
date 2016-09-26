<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Url extends BaseRule {

	/**
	 * override
	 */
	protected $message ="正しいURLを入力してください";

	/**
	 * override
	 */
	public function check ($value) {

		return  ! strlen($value) || preg_match('/^https?:\/\/[-_.!~*\''.
				'()a-zA-Z0-9;\/?:\@&=+\$,%#]+$/', $value);
	}
}