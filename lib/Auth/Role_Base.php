<?php
namespace R\Lib\Auth;

/**
 *
 */
abstract class Role_Base
{
    protected $account_manager;
    protected $state;
    protected $role_name;

    /**
     * ログイン試行
     */
    abstract public function loginTrial ($state);

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
    public function __construct ($account_manager, $role_name)
    {
        $this->account_manager = $account_manager;
        $this->role_name = $role_name;
        $this->state = $this->getTmpStorage()->get("state");
    }

    /**
     * @getter
     */
    public function getRole ()
    {
        return $this->role_name;
    }

    /**
     * @getter
     */
    public function getId ()
    {
        return $this->state["id"];
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
    public function isLogin ()
    {
        return (bool)$this->state["login"];
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
        if ( ! $this->isLogin()) {
            return false;
        }
        // trueの指定の場合ログインしているかどうかのみ確認
        if ($priv === true) {
            return true;
        }
        // 権限を持つか確認
        foreach ((array)$this->state["privs"] as $priv_check) {
            if ($priv_check == $priv) {
                return true;
            }
        }
        return false;
    }

    /**
     * 状態の更新
     */
    public function setState ($state)
    {
        $this->state = $state;
        $this->getTmpStorage()->set("state",$state);
    }

    /**
     * 保存領域の確保
     */
    protected function getTmpStorage ()
    {
        if ( ! isset($this->tmp_storage)) {
            $this->tmp_storage = session(__CLASS__)
                ->session("tmp_storage")
                ->session($this->role_name);
        }
        return $this->tmp_storage;
    }
}