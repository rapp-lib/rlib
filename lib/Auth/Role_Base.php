<?php
namespace R\Lib\Auth;

/**
 *
 */
abstract class Role_Base
{
    protected $account_manager;
    protected $role_name;
    protected $state;
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
    /**
     * @override
     */
    public function __construct ($account_manager, $role_name)
    {
        $this->account_manager = $account_manager;
        $this->role_name = $role_name;
    }
    /**
     * @setter
     */
    public function initState ($state)
    {
        $this->state = $state;
    }
    /**
     * @getter
     */
    public function getAccountManager ()
    {
        return $this->account_manager;
    }
    /**
     * @getter
     */
    public function getRoleName ()
    {
        return $this->role_name;
    }
    /**
     * @getter
     */
    public function getState ($name)
    {
        return $this->state[$name];
    }
    /**
     * @getter
     */
    public function getId ()
    {
        return $this->getState("id");
    }
    /**
     * @getter
     */
    public function isLogin ()
    {
        return (bool)$this->getState("login");
    }
    /**
     * 権限を持つかどうか確認
     */
    public function check ($priv)
    {
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
        return $this->getRoleName();
    }
}