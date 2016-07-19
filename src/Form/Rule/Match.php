<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
class Match extends BaseRule {

	/**
	 * override
	 */
	protected $message ="一致しています";

	/**
	 * override
	 */
	public function check ($value) {

		return  strcmp($value,$option) == 0;
	}
}