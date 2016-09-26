<@?php

/**
 * Context: <?=$c["account"]?>認証
 */
class <?=str_camelize($c["account"])?>AuthContext extends Context_App 
{
	/**
	 * ログインチャレンジ処理
	 */
	public function login ($login_id, $login_pass) 
	{
		// ログインチャレンジ時は事前にログアウト処理
		$this->logout();
		
		// ログインID/パスワードチェック
		$id =($login_id == "admin" && $login_pass == "cftyuhbvg") ? 1 : null;
		
		if ($id) {
			$this->id($id);
			session_regenerate_id(true);
		}
	}

	/**
	 * ログアウト処理
	 */
	public function logout () 
	{
		// ログイン時に使用していたセッション情報は破棄
		session_destroy();
	}
}
