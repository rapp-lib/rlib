<?php
namespace R\Lib\Auth;

/**
 *
 */
abstract class Role_Base
{
    protected $account_manager;
    protected $attrs;

    /**
     * ログイン試行
     */
    abstract public function loginTrial ($attrs);

    /**
     * ログイン時の処理
     */
    abstract public function onLogin ();

    /**
     * ログアウト時の処理
     */
    abstract public function onLogout ();

    /**
     * アクセス時の処理
     */
    abstract public function onAccess ();

    /**
     * 認証要求時の処理
     */
    abstract public function onLoginRequired ($required);

    /**
     * @override
     */
    public function __constract ($account_manager)
    {
        $this->account_manager = $account_manager;
    }

    /**
     * @getter
     */
    public function getRole ()
    {
        return $this->attrs["role"];
    }

    /**
     * @getter
     */
    public function getId ()
    {
        return $this->attrs["id"];
    }

    /**
     * @getter
     */
    public function getAttr ($name)
    {
        return $this->attrs[$name];
    }

    /**
     * @getter
     */
    public function isLogin ()
    {
        return (bool)$this->attrs["login"];
    }

    /**
     * 権限を持つかどうか確認
     */
    public function check ($priv)
    {
        // 複数指定の場合、全ての権限を持つか確認する
        if (is_array($priv)) {
            foreach ($priv as $v) {
                if ( ! $this->check($v)) {
                    return false;
                }
            }
            return true;
        }

        // trueの指定の場合ログインしているかどうかのみ確認
        if ($priv === true) {
            return $this->attrs["login"];
        }

        // 権限を持つか確認
        foreach ((array)$this->attrs["privs"] as $priv_check) {
            if ($priv_check == $priv) {
                return true;
            }
        }

        return false;
    }

    /**
     * 更新処理
     */
    public function reset ($attrs)
    {
        $this->attrs = $attrs;
    }
}