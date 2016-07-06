<?php

namespace R\Lib\Rapper\Mod;

/**
 * 
 */
class BaseMod {

	/**
	 * [$r description]
	 * @var [type]
	 */
	protected $r;

	/**
	 * [__construct description]
	 * @param Rapper $r [description]
	 */
	public function __construct ($r)
	{
		$this->r =$r;
	}

	/**
	 * [install description]
	 * @param  Rapper $r [description]
	 * @return [type]    [description]
	 */
	public function install () 
	{
	}
}