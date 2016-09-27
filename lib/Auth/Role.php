<?php
namespace R\Lib\Auth;

/**
 *
 */
abstract class Role
{
    protected $attrs;

    /**
     * ログイン処理
     */
    abstract public function onLogin ($attrs);

    /**
     * ログアウト処理
     */
    abstract public function onLogout ();

    /**
     * 認証確認時の処理
     */
    abstract public function onBeforeAuthenticate ();

    /**
     * 認証否認時の処理
     */
    abstract public function onLoginRequired ();

    /**
     * @override
     */
    public function __constract ($attrs)
    {
        $this->attrs = $attrs;
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
     * 権限を持つかどうか確認
     */
    public function hasPriv ($priv)
    {
        if ($this->attrs["role"] == $priv) {
            return true;
        }
        foreach ((array)$this->attrs["privs"] as $priv_check) {
            if ($priv_check == $priv) {
                return true;
            }
        }
        return false;
    }
}