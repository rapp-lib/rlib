<?php

namespace R\Lib\Query;

/**
 * 
 */
class St
{
	/**
	 * [stSelect description]
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	public static function select ($query)
	{
		return dbi()->st_select($query);
	}
}