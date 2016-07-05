<?php

namespace R\Lib\Core;

/**
 * 
 */
class Html {

	/**
	 * HTMLタグの組み立て
	 * @param  [type] $name    [description]
	 * @param  [type] $attrs   [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public static function tag ($name, $attrs=null, $content=null) {
		
		return tag($name, $attrs, $content);
	}
}