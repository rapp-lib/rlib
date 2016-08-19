<?php

namespace R\Lib\Query\Search;
use R\Lib\Query\St;

/**
 * 
 */
class Compare extends BaseSearch {

	protected $setting;

	public function __construct ($setting) {

		$this->setting =$setting;
	}

	public function getQuery ($input) {
		
		$op =$this->setting["op"]
				? $this->setting["op"]
				: "=";
		
		return strlen($input)
				? array($this->setting["target"].' '.$op =>$input)
				: null;
	}
}