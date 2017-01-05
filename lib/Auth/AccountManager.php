<?php
namespace R\Lib\Auth;

/**
 *
 */
class AccountManager
{
    private static $instance = null;
    /**
     * 認証済みのRole名
     */
    private $auth_role_name = null;
    private $accounts = array();
    private $account_states = array();
    /**
     * 指定したアカウント、またはAccountManagerインスタンスを返す
     */
    public static function getInstance ($role_name=false)
    {
        if ( ! self::$instance) {
            self::$instance = new AccountManager();
        }
        return $role_name!==false
            ? self::$instance->getAccount($role_name)
            : self::$instance;
    }
    /**
     * 指定したアカウントを取得する
     */
    public function getAccount ($role_name=false)
    {
        // 認証済みアカウントを取得
        if ($role_name===false) {
            $role_name = $this->auth_role_name ? $this->auth_role_name : "none";
        }
        if ( ! $this->accounts[$role_name]) {
            // インスタンスの作成
            $class = $role_name=="none"
                ? "R\\Lib\\Auth\\NoneRole"
                : "R\\App\\Role\\".str_camelize($role_name)."Role";
            $this->accounts[$role_name] = new $class($this, $role_name);
        }
        return $this->accounts[$role_name];
    }
    /**
     * 認証を行う
     */
    public function authenticate ($role_name, $required=false)
    {
        // 既に認証済みであれば多重認証処理エラー
        // ※複数のRoleでアクセスする可能性がある場合は共用Roleを用意する
        if ($this->auth_role_name) {
            report_warning("多重認証エラー",array(
                "role" => $role_name,
                "auth_role_name" => $this->auth_role_name,
            ));
            return false;
        }
        $this->auth_role_name = $role_name;
        // 認証時の処理呼び出し
        $this->getAccount()->onAccess();
        // ログイン必須チェック
        if ($required && ! $this->getAccount()->check($required)) {
            // アクセス要求時の処理呼び出し
            $this->getAccount()->onLoginRequired($required);
            return false;
        }
        return true;
    }
    /**
     * 認証処理が完了しているかどうか
     */
    public function checkAuthenticated ()
    {
        return (bool)$this->auth_role_name;
    }
    /**
     * 認証状態の確認
     */
    public function check ($role_name, $required=true)
    {
        if ($this->auth_role_name !== $role_name) {
            return false;
        }
        return $this->getAccount($role_name)->check($required);
    }
    /**
     * ログイン処理を行う
     */
    public function login ($role_name, $params)
    {
        // ログイン試行処理の呼び出し
        $result = $this->getAccount($role_name)->loginTrial($params);
        if ( ! $result) {
            // ログイン失敗時には状態を初期化
            $this->saveAccountState($role_name, array());
            return false;
        }
        // ログインしたアカウントの状態を更新
        $result["login"] = true;
        $result["id"] = (string)$result["id"];
        $result["privs"] = (array)$result["privs"];
        $this->saveAccountState($role_name, $result);
        // ログイン時の処理呼び出し
        $this->getAccount($role_name)->onLogin();
        return true;
    }
    /**
     * ログアウト処理を行う
     */
    public function logout ($role_name)
    {
        // ログアウト時の処理呼び出し
        $this->getAccount($role_name)->onLogout();
        // 状態を初期化
        $this->saveAccountState($role_name, array());
    }
    /**
     * 保存領域の確保
     */
    private function saveAccountState ($role_name, $state)
    {
        app()->session(__CLASS__)->session("roles")->session($role_name)->set("account_state",$state);
    }
    /**
     * 保存領域の確保
     */
    public function restoreAccountState ($role_name)
    {
        return app()->session(__CLASS__)->session("roles")->session($role_name)->get("account_state");
    }
}
