<?php
namespace R\Lib\Auth;

/**
 * 
 */
class Role_Base
{
	/**
	 * 
	 */
	public function login ($login_id, $login_pw)
	{
	}

	/**
	 * 
	 */
	public function logout ()
	{}

	/**
	 * 
	 */
	public function reminder ($reminder_cred)
	{}

	/**
	 * 
	 */
	public function on_access_denied ()
	{}
}