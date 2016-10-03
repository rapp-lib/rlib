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
     * 認証確認前の処理
     */
    abstract public function onBeforeAuthenticate ();

    /**
     * 認証否認時の処理
     */
    abstract public function onLoginRequired ();

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

        // trueの指定の場合Role名での確認と同義とする
        if ($priv === true) {
            $priv = $this->attrs["role"];
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