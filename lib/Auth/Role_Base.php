<?php
namespace R\Lib\Auth;

abstract class Role_Base
{
    protected $session;
    protected $login_state = array();
    public function __construct ()
    {
        // Session領域の確保
        $this->session = app()->session(get_class($this));
        // 記憶されている認証情報の復帰
        $login_state = $this->session->get("login_state");
        if ($login_state) {
            $this->login_state = $login_state;
        }
        $this->onAccess();
    }
    public function __get ($name)
    {
        if ($name==="role_name") {
            return $this->getRoleName();
        }
        return $this->getState($name);
    }
    /**
     * 認証処理
     */
    public function login ($params)
    {
        $this->login_state = array();
        $this->session->delete("login_state");
        $login_state = $this->loginTrial($params);
        // 認証成功時の認証情報の記憶
        if ($login_state) {
            $this->login_state = $login_state;
            $this->session->set("login_state", $login_state);
            $this->onLogin();
            return true;
        } else {
            return false;
        }
    }
    /**
     * 認証情報の解除
     */
    public function logout ()
    {
        $this->onLogout();
        $this->login_state = array();
        $this->session->delete("login_state");
    }
    /**
     * 強制的な認可処理
     * 否認時には規定の認可否認処理を行う
     */
    public function requirePriv ($priv_required)
    {
        if ( ! $this->hasPriv($priv_required)) {
            // 認可否認時の応答をResponseException経由で発行
            $response = $this->onLoginRequired($required);
            $response->raise();
        }
    }

// -- 認証情報の取得

    /**
     * 認証情報を取得する
     */
    public function getState ($name)
    {
        return $this->login_state[$name];
    }
    /**
     * 認証されている利用者を特定するIDを取得
     */
    public function getId ()
    {
        return $this->getState("id");
    }
    /**
     * 利用者が認証されているかどうかを取得
     */
    public function isLogin ()
    {
        return $this->hasPriv(true);
    }
    /**
     * 認可処理
     * 指定された認証情報（権限）を持つかどうかを取得
     */
    public function hasPriv ($priv)
    {
        // falseであれば役割のみの確認
        if ($priv===false) {
            return true;
        // trueであれば認証されているかどうかの確認
        } elseif ($priv===true) {
            return $this->getId() !== null;
        // 指定された名前の認証情報の有無により確認
        } else {
            return $this->getState($priv) !== null;
        }
    }
    /**
     * @getter
     */
    public function getRoleName ()
    {
        if (preg_match('!(\w+)Role$!',get_class($this),$match)) {
            return str_underscore($match[1]);
        } else {
            report_error("Roleのクラス名が不正です",array(
                "class" => get_class($this),
            ));
        }
    }

// -- 旧認証系実装

    /**
     * ログイン試行
     */
    public function loginTrial ($params)
    {
        return false;
    }
    /**
     * ログイン時の処理
     */
    public function onLogin ()
    {
    }
    /**
     * ログアウト時の処理
     */
    public function onLogout ()
    {
    }
    /**
     * アクセス時の処理
     */
    public function onAccess ()
    {
    }
    /**
     * 認証要求時の処理
     */
    public function onLoginRequired ($required)
    {
    }

// -- 廃止予定

    protected $account_manager;
    protected $state;
    /**
     * @setter
     */
    public function initState ($state)
    {
        report_error("@deprecated Role::getRoleName");
        $this->state = $state;
    }
    /**
     * @getter
     */
    public function getAccountManager ()
    {
        report_warning("@deprecated Role::getRoleName");
        return app()->auth;
    }
    /**
     * 権限を持つかどうか確認
     */
    public function check ($priv)
    {
        report_warning("@deprecated Role::check");
        return $this->hasPriv($priv);
        if ( ! $this->isLogin()) {
            return false;
        }
        // trueの指定の場合ログインしているかどうかのみ確認
        if ($priv === true) {
            return true;
        }
        // 複数指定の場合、全ての権限を持つか確認する
        if (is_array($priv)) {
            foreach ($priv as $v) {
                if ( ! $this->check($v)) {
                    return false;
                }
            }
            return true;
        }
        // 権限を持つか確認
        foreach ((array)$this->getState("privs") as $priv_check) {
            if ($priv_check == $priv) {
                return true;
            }
        }
        return false;
    }
    /**
     * @getter
     * @deprecated
     */
    public function getRole ()
    {
        report_warning("@deprecated Role::getRole");
        return $this->getRoleName();
    }
}