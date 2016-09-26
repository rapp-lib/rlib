<?php

namespace R\Lib\Query\Search;
use R\Lib\Query\St;

/**
 * 
 */
class In extends BaseSearch {

	protected $setting;

	public function __construct ($setting) {

		$this->setting =$setting;
	}

	public function getQuery ($input) {
		
		// target IN (...query... AND query_target=input)
		if ($this->setting["query"] && $this->setting["query_target"]) {
		
			$query =$this->setting["query"];
			$query["conditions"][] =array($this->setting["query_target"] => $input);

			return array($this->setting["target"].' IN ('.Query::stSelect($query).')');
		
		// EXISTS (...query... AND target = input)
		} elseif ($this->setting["query"]) {
		
			$query =$this->setting["query"];
			$query["conditions"][] =array($this->setting["target"] => $input);

			return array('EXISTS ('.St::select($query).')');
		
		// target = input
		} else {
			
			return array($this->setting["target"] =>$input);
		}
	}
}