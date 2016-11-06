<?php
namespace R\Lib\Auth;

/**
 *
 */
class AccountManager
{
    private $access_role = null;
    private $accounts = array();
    private $account_attrs = array();
    /**
     * 指定したアカウント、またはAccountManagerインスタンスを返す
     */
    public static function getInstance ($name=null)
    {
        $account_manager = & ref_globals("account_manager");
        if ( ! $account_manager) {
            $account_manager = new self();
        }
        return isset($name)
            ? $account_manager->getAccount($name)
            : $account_manager;
    }
    /**
     * @overload
     */
    public function __construct ()
    {
        $this->account_attrs = & ref_session("AccountManager_account_attrs");
    }
    /**
     * 指定したアカウントを取得する
     */
    public function getAccount ($role=null)
    {
        // 指定が無ければ認証済みアカウントを取得
        if ( ! $role) {
            if ( ! $this->access_role) {
                report_error("未認証エラー",array());
            }
            $role = $this->access_role;
        }

        if ( ! $this->accounts[$role]) {
            // インスタンスの作成
            $class = "R\\App\\Role\\".str_camelize($role)."Role";
            $this->accounts[$role] = new $class($this);

            // セッションからの復帰
            $login_account_attr = (array)$this->account_attrs[$role];
            $this->accounts[$role]->reset($login_account_attr);
        }

        return $this->accounts[$role];
    }
    /**
     * 認証を行う
     */
    public function authenticate ($role, $required=true)
    {
        if ( ! $role) {
            report_error("認証エラー",array(
                "role" => $role,
                "required" => $required,
            ));
        }
        // 既に認証済みであれば多重認証処理エラー
        // ※複数のRoleでアクセスする可能性がある場合は共用Roleを用意する
        if ($this->access_role) {
            report_error("多重認証エラー",array(
                "role" => $role,
                "access_role" => $this->access_role,
            ));
        }
        $this->access_role = $role;
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
        return $this->access_role;
    }
    /**
     * ログイン処理を行う
     */
    public function login ($role, $params)
    {
        // セッション情報を初期化
        $this->resetLoginAccount($role);

        // ログイン試行処理の呼び出し
        $result = $this->getAccount($role)->loginTrial($params);
        if ( ! $result) {
            return false;
        }

        // ログインしたアカウントのセッション情報を更新
        $result["role"] = $role;
        $result["login"] = true;
        $result["id"] = (string)$result["id"];
        $result["privs"] = (array)$result["privs"];
        $this->resetLoginAccount($role, $result);

        // ログイン時の処理呼び出し
        $this->getAccount($role)->onLogin();

        return true;
    }
    /**
     * ログアウト処理を行う
     */
    public function logout ($role)
    {
        // セッション情報を初期化
        $this->resetLoginAccount($role);
        // ログアウト時の処理呼び出し
        $this->getAccount($role)->onLogout($params);
    }
    /**
     * ログインセッション情報を更新する
     */
    private function resetLoginAccount ($role, $login_account_attr=null)
    {
        if ($login_account_attr) {
            $this->account_attrs[$role] = $login_account_attr;
        } else {
            unset($this->account_attrs[$role]);
        }

        $this->getAccount($role)->reset($login_account_attr);
    }
}