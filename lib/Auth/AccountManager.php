<?php
namespace R\Lib\Auth;
/*
	auth()->login("member",array(
		"login_id"=>$login_id, 
		"login_pw"=>md5($login_pw))));
	if (auth("member")->check()) {
		
	} else {

	}
*/

/**
 * 
 */
class AccountManager
{
	private $auth_role = null;
	private $login_accounts = array();
	private $login_account_attrs;

	/**
	 * 指定したアカウント、またはAccountManagerインスタンスを返す
	 */
	public static function load ($name=null)
	{
		$account_manager = & ref_globals("account_manager");

		if ( ! $account_manager) {
			$account_manager = new R\Lib\AccountManager();
		}

		return $name===null
			? $account_manager
			: $account_manager->getLoginAccount($name);
	}

	/**
	 * @overload
	 */
	public function __construct ()
	{
		$this->login_account_attrs = & ref_session("AccountManager_login_account_attrs");
	}

	/**
	 * 認証中のアカウントを取得する
	 */
	public function getAuthAccount ()
	{
		return $this->getLoginAccount($this->auth_role);
	}

	/**
	 * ログインアカウントを取得する
	 */
	public function getLoginAccount ($role)
	{
		if ( ! $this->login_accounts[$role]) {
			// インスタンスの作成
			$class = $this->getRoleClass($role);
			$this->login_accounts[$role] = new $class;

			// セッションからの復帰
			$login_account_attr = (array)$this->login_account_attrs[$role];
			$this->login_accounts[$role]->onReset($login_account_attr);
		}

		return $this->login_account[$role];
	}

	/**
	 * ログインアカウントを更新する
	 */
	private function resetLoginAccount ($role, $login_account_attr=null)
	{
		if ($login_account_attr) {
			$this->login_account_attrs[$role] = $login_account_attr;
		} else {
			unset($this->login_account_attrs[$role]);
		}

		$this->login_accounts[$role]->onReset($login_account_attr);
	}

	/**
	 * 認証を行う
	 */
	public function authenticate ($role, $required=true, $privs_required=array())
	{
		// 既に認証済みであれば多重認証処理エラー
		// ※複数のRoleでアクセスを許可する場合は共用Roleを用意すること
		if ($this->auth_role) {
			report_error("多重認証エラー",array(
				"role" => $role,
				"auth_role" => $this->auth_role,
			));
		}
		$this->auth_role = $role;

		$account = $this->getLoginAccount($role);
		
		// 認証前処理
		$account->onBeforeAuthenticate();
		
		// ログイン必須チェック
		if ($required && ! $account->check()) {
			$account->onLoginRequired();
			return false;
		}

		// 権限必須チェック
		if ($required && ! $account->hasPriv($privs_required)) {
			$account->onPrivRequired();
			return false;
		}

		return true;
	}

	/**
	 * ログイン処理を行う
	 */
	public function login ($role, $params)
	{
		$this->resetLoginAccount($role);
		$result = $this->getLoginAccount($role)->onLogin($params);
		if ($result) {
			$this->resetLoginAccount($role, array(
				"role" => $role,
				"id" => $result["id"],
				"privs" => $result["privs"],
				"attrs" => $result["attrs"],
			));
		}
	}

	/**
	 * ログアウト処理を行う
	 */
	public function logout ($role)
	{
		$this->resetLoginAccount($role);
		$this->getLoginAccount($role)->onLogout($params);
	}

	/**
	 * アカウント生成用のRoleクラスを取得
	 */
	private function getRoleClass ($role)
	{
		$ns = "R\\App\\Auth\\Role\\";
		$role_class = str_camelize($role);

		if (class_exists($role_class)) {
			return $role_class;
		}

		return $ns.$role_class;
	}
}