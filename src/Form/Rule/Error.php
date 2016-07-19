<?php

namespace R\Lib\Form\Rule;

/**
 * 必ずエラーを発行する
 */
class Error extends BaseRule {

	/**
	 * override
	 */
	protected $message ="入力が不正です";

	/**
	 * override
	 */
	public function check ($value) {

		return ! $this->params["option"];
	}
}