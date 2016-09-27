<?php
namespace R\Lib\Auth;

/**
 * 
 */
class AccountManager
{
	/**
	 * ログイン中のアカウントを取得する
	 */
	public static function getAccount ($role)
	{
		// ログイン中のアカウントがある場合
		foreach (self::$login_accounts as $login_role => $info) {
			if (self::isSuperRoleOf($login_role,$role)) {
				$role_class = self::getRoleClass($login_role);
				return new $role_class($info);
			}
		}

		// ログイン中のアカウントがない場合
		$role_class = self::getRoleClass($role);
		return new $role_class();
	}

	/**
	 * 
	 */
	public static function loginAs ($role, $id, $attrs=array())
	{
		self::logoutAs($role);

		self::$login_accounts[$role] = array(
			"role" => $role,
			"id" => $id,
			"attrs" => $attrs,
		);
	}

	/**
	 * 
	 */
	public static function logoutAs ($role)
	{
		// ログイン中のアカウントがある場合
		foreach (self::$login_accounts as $login_role => $info) {
			if (self::isSuperRoleOf($login_role,$role)) {
				unset(self::$login_accounts[$login_role]);
			}
			if (self::isSuperRoleOf($role,$login_role)) {
				unset(self::$login_accounts[$login_role]);
			}
		}
	}

	/**
	 * 
	 */
	private static function getRoleClass ($role)
	{
		$ns = "R\\App\\Role\\";
		$role_class = str_camelize($role);

		if (class_exists($role_class)) {
			return $role_class;
		}

		return $ns.$role_class;
	}

	/**
	 * 
	 */
	private static function isSuperRoleOf ($role, $target_role)
	{
		if ($role == $target_role) {
			return true;
		}

		$role_class = self::getRoleClass($role);
		$target_role_class = self::getRoleClass($target_role);

		return is_subclass_of($target_role_class, $role_class);
	}
}