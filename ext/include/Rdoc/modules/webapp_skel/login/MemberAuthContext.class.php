<!?php

/**
 * @context
 */
class <?=str_camelize($c["account"])?>AuthContext extends Context_App
{

    /**
     * ログイン処理
     */
    public function login ($login_id, $login_pass)
    {
        // ログインチャレンジ時は事前にログアウト処理
        $this->logout();

        // ログインID/パスワードチェック
        $id =($login_id == "admin" && $login_pass == "cftyuhbvg") ? 1 : null;

        if ($id) {
            $this->id($id);
            $this->refresh();

            // Session Fixation対策
            session_regenerate_id(true);
        }
    }

    /**
     * ログイン状態の更新
     */
    public function refresh ()
    {
    }

    /**
     * ログアウト処理
     */
    public function logout ()
    {
        $this->session(false,false);
    }
}
