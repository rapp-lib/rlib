<?php
namespace R\Lib\Auth;

use R\Lib\Core\Contract\InvokableProvider;

class AccountManager implements InvokableProvider
{
    /**
     * @override InvokableProvider
     */
    public function invoke ($role_name=false)
    {
        return $role_name===false ? $this->getAccessRole() : $this->getAccountRole($role_name);
    }
    /**
     * Containerの初期化時に決定される利用者に求めるRoleの名前
     */
    protected $access_role_name;
    /**
     * RoleのSingletonインスタンス
     */
    protected $account_roles = array();
    /**
     * 初期化
     */
    public function __construct ()
    {
        // AccessRoleの決定
        $this->access_role_name = app()->router->getCurrentRoute()->getController()->getAccessRoleName();
    }
    /**
     * AccessRoleの認可確認
     */
    public function check ($role_name, $priv_required=false)
    {
        if ($this->access_role_name !== $role_name) {
            return false;
        }
        if ($required !== false && ! $this->getAccessAccount()->hasPriv($priv_required)) {
            return false;
        }
        return true;
    }
    /**
     * AccessRoleを取得する
     */
    public function getAccessRole ()
    {
        if ( ! $this->access_role_name) {
            report_error("AccessRoleが不正です");
        }
        return $this->getAccountRole($this->access_role_name);
    }
    /**
     * 指定したRoleを取得する
     */
    public function getAccountRole ($role_name)
    {
        if ( ! isset($this->account_roles[$role_name])) {
            $class = 'R\App\Role\\'.str_camelize($role_name).'Role';
            $this->account_roles[$role_name] = new $class($this, $role_name);
        }
        return $this->account_roles[$role_name];
    }

// -- 廃止予定

    /**
     * 認証済みのRole名
     */
    private $auth_role_name = null;
    private $accounts = array();
    private $account_states = array();
    /**
     * 指定したアカウントを取得する
     */
    public function getAccount ($role_name=false)
    {
        report_warning("@deprecated AccountManager::getAccount");
        return $role_name===false ? $this->getAccessRole() : $this->getAccountRole($role_name);
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
            $this->restoreAccountState($role_name);
        }
        return $this->accounts[$role_name];
    }
    /**
     * 認証を行う
     */
    public function authenticate ($role_name, $required=false)
    {
        report_error("@deprecated AccountManager::authenticate");
        // 既に認証済みであれば多重認証処理エラー
        // ※複数のRoleでアクセスする可能性がある場合は共用Roleを用意する
        if ($this->auth_role_name) {
            report_error("多重認証エラー",array(
                "role" => $role_name,
                "auth_role_name" => $this->auth_role_name,
            ));
        }
        $this->auth_role_name = $role_name;
        // 認証時の処理呼び出し
        $this->getAccount()->onAccess();
        // ログイン必須チェック
        if ($required && ! $this->getAccount()->check($required)) {
            // アクセス要求時の処理呼び出し
            return $this->getAccount()->onLoginRequired($required);
        }
    }
    /**
     * 認証処理が完了しているかどうか
     */
    public function checkAuthenticated ()
    {
        report_error("@deprecated AccountManager::checkAuthenticated");
        return (bool)$this->auth_role_name;
    }
    /**
     * アカウントの状態の更新
     */
    private function saveAccountState ($role_name, $state)
    {
        report_error("@deprecated AccountManager::checkAuthenticated");
        $this->getAccount($role_name)->initState($state);
        app()->session(__CLASS__)->session("roles")->session($role_name)->set("account_state",$state);
    }
    /**
     * アカウントの状態の反映
     */
    private function restoreAccountState ($role_name)
    {
        report_error("@deprecated AccountManager::checkAuthenticated");
        $state = app()->session(__CLASS__)->session("roles")->session($role_name)->get("account_state");
        $this->getAccount($role_name)->initState($state);
    }
    /**
     * ログイン処理を行う
     */
    public function login ($role_name, $params)
    {
        report_warning("@deprecated AccountManager::login");
        return $this->getAccountRole($role_name)->login($params);
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
        report_warning("@deprecated AccountManager::logout");
        return $this->getAccountRole($role_name)->logout();
        // ログアウト時の処理呼び出し
        $this->getAccount($role_name)->onLogout();
        // 状態を初期化
        $this->saveAccountState($role_name, array());
    }
}
