<?php
namespace R\Lib\Auth;

/**
 *
 */
class AccountManager
{
    private $access_role = null;
    private $login_role = null;
    private $login_accounts = array();
    private $login_account_attrs;

    /**
     * 指定したアカウント、またはAccountManagerインスタンスを返す
     */
    public static function load ($name=null)
    {
        $account_manager = & ref_globals("account_manager");

        if ( ! $account_manager) {
            $account_manager = new self();
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
    public function getAccessAccount ()
    {
        if ( ! $this->access_role) {
            return null;
        }
        return $this->getLoginAccount($this->access_role);
    }

    /**
     * ログイン中のアカウントを取得する
     */
    public function getLoginAccount ($role=null)
    {
        if ( ! $role) {
            $role = $this->login_role;
        }

        if ( ! $role) {
            return null;
        }

        if ( ! $this->login_accounts[$role]) {
            // インスタンスの作成
            $class = $this->getRoleClass($role);
            $this->login_accounts[$role] = new $class($this);

            // セッションからの復帰
            $login_account_attr = (array)$this->login_account_attrs[$role];
            $this->login_accounts[$role]->reset($login_account_attr);
        }

        return $this->login_accounts[$role];
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

        $this->getLoginAccount($role)->reset($login_account_attr);
    }

    /**
     * 認証を行う
     */
    public function authenticate ($role, $required=true)
    {
        if ( ! $role) {
            return ! $required;
        }

        // 既に認証済みであれば多重認証処理エラー
        // ※複数のRoleでアクセスを許可する場合は共用Roleを用意すること
        if ($this->access_role) {
            report_error("多重認証エラー",array(
                "role" => $role,
                "access_role" => $this->access_role,
            ));
        }
        $this->access_role = $role;

        $account = $this->getAccessAccount();

        // 認証前処理
        $account->onBeforeAuthenticate();

        // ログイン必須チェック
        if ($required && ! $account->check($required)) {
            $account->onLoginRequired();
            return false;
        }

        $account->login_role = $role;

        return true;
    }

    /**
     * ログイン処理を行う
     */
    public function login ($role, $params)
    {
        $this->resetLoginAccount($role);
        $result = $this->getLoginAccount($role)->loginTrial($params);

        if ( ! $result) {
            return false;
        }

        $result["role"] = $role;
        $result["id"] = (string)$result["id"];
        $result["privs"] = (array)$result["privs"];
        $this->resetLoginAccount($role, $result);

        $this->getLoginAccount($role)->onLogin();

        return true;
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
        $ns = "R\\App\\Role\\";
        $role_class = str_camelize($role)."Role";

        if (class_exists($role_class)) {
            return $role_class;
        }

        return $ns.$role_class;
    }
}