<?php
namespace R\App\Auth;
use R\Lib\Auth\Role;

/**
 * 
 */
class MemberRole extends Role
{
	/**
	 * ログイン処理
	 */
	public function onLogin ($params)
	{
		$t = array();
		
		if ($params["login_id"]) {
			$t = table("Member")
				->where(array("login_id"=>$params["login_id"]))
				->where(array("login_pw"=>md5($params["login_pw"])))
				->selectOne(array("id","privs"));
		}
		
		if ($params["reminder_hash"]) {
			$t = table("Member")
				->where(array("reminder_hash"=>$params["reminder_hash"]))
				->where(array("reminder_hash_expire >"=>time()))
				->selectOne(array("id","privs"));
		}
		
		if ($params["cookie_cred"]) {
			$t = table("Member")
				->where(array("cookie_cred"=>$params["cookie_cred"]))
				->where(array("cookie_expire >"=>time()))
				->selectOne(array("id","privs"));
		}
		
		if ( ! $t) {
			return false;
		}

		if ($params["cookie_remember"]) {
			if ($t) {
				$cookie_cred = md5(time().mt_rand(0,10000000));
				$cookie_expire =time()+3600*24*14;
				table("Member")
					->values(array("cookie_cred"=>$cookie_cred))
					->values(array("cookie_expire"=>$cookie_expire))
					->update($t["id"]);
				
				setcookie("auth_member_cookie_cred",$cookie_cred,$cookie_expire);
			} else {
				setcookie("auth_member_cookie_cred",false);
			}
		}
		
		return array(
			"id" => $t["id"],
			"privs" => unserialize($t["privs"]),
			"attrs" => array(),
		);
	}

	/**
	 * ログアウト処理
	 */
	public function onLogout ()
	{
	}

	/**
	 * 認証確認の処理
	 */
	public function onBeforeAuthenticate ()
	{
		$cookie_cred = $_COOKIE["auth_member_cookie_cred"];
		if ( ! $this->check() && $cookie_cred) {
			AccountManager::login("member", array("cookie_cred" => $cookie_cred));
		}
	}

	/**
	 * 認証否認時の処理
	 */
	public function onLoginRequired ()
	{
		app()->redirect("page:member_login",array(
			"redirect_to" => app("request")->url(),
		));
	}
}