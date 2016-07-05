<?php

namespace R\Lib\Form\Rule;

/**
 * 
 */
abstract class BaseRule {

	protected $message ="入力が不正です";

	/**
	 * 
	 */
	public function check ($value, $params) {

		return false;
	}
	
	/**
	 * 
	 */
	public function getMessage () {

		return $this->message;
	}
}