<?php

	/**
	 * @facade R\Lib\AccountManager::load
	 */
	function auth ($name=null)
	{
		R\Lib\AccountManager::load($name);
	}