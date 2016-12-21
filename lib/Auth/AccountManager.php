<?php
namespace R\Lib\Auth;

/**
 *
 */
class AccountManager
{
    private $access_role = null;
    private $accounts = array();
    private $tmp_storages = array();
    /**
     * 指定したアカウント、またはAccountManagerインスタンスを返す
     */
    public static function getInstance ($name=false)
    {
        $account_manager = & ref_globals("account_manager");
        if ( ! $account_manager) {
            $account_manager = new self();
        }
        return $name!==false
            ? $account_manager->getAccount($name)
            : $account_manager;
    }
    /**
     * 指定したアカウントを取得する
     */
    public function getAccount ($role=false)
    {
        // 指定が無ければ認証済みアカウントを取得
        if ($role===false) {
            if ( ! $this->access_role) {
                report_warning("未認証エラー");
                return null;
            }
            $role = $this->access_role;
        }
        if ( ! $this->accounts[$role]) {
            // インスタンスの作成
            $class = "R\\App\\Role\\".str_camelize($role)."Role";
            $this->accounts[$role] = new $class($this, $role);
        }

        return $this->accounts[$role];
    }
    /**
     * 認証を行う
     */
    public function authenticate ($role, $required=true)
    {
        if ( ! $role) {
            report_warning("認証エラー",array(
                "role" => $role,
                "required" => $required,
            ));
            return false;
        }
        // 既に認証済みであれば多重認証処理エラー
        // ※複数のRoleでアクセスする可能性がある場合は共用Roleを用意する
        if ($this->access_role) {
            report_warning("多重認証エラー",array(
                "role" => $role,
                "access_role" => $this->access_role,
            ));
            return false;
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
        // 状態を初期化
        $this->getAccount($role)->setState(array());

        // ログイン試行処理の呼び出し
        $result = $this->getAccount($role)->loginTrial($params);
        if ( ! $result) {
            return false;
        }

        // ログインしたアカウントの状態を更新
        $result["login"] = true;
        $result["id"] = (string)$result["id"];
        $result["privs"] = (array)$result["privs"];
        $this->getAccount($role)->setState($result);

        // ログイン時の処理呼び出し
        $this->getAccount($role)->onLogin();

        return true;
    }
    /**
     * ログアウト処理を行う
     */
    public function logout ($role)
    {
        // 状態を初期化
        $this->getAccount($role)->setState(array());
        // ログアウト時の処理呼び出し
        $this->getAccount($role)->onLogout($params);
    }
}