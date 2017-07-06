<?php
namespace R\Lib\Auth;

class UserLoginDriver
{
    /**
     * 現在のロール、明示的に切り替えない限りロールはguest
     */
    private $role = "guest";
    /**
     * 現在のロールと一致する場合のみ、付与されている権限を取得
     */
    public function getPriv($role)
    {
        return $role===$this->role ? $this->getLoginProvider($role)->getPriv($role) : false;
    }
    /**
     * 指定ロールの権限を設定
     * 主にログイン成功直後に使用、ロールは切り替わらない
     */
    public function setPriv($role, $priv)
    {
        $this->getLoginProvider($role)->setPriv($priv);
    }
    /**
     * 現在のロールを取得
     */
    public function getRole()
    {
        return $this->role;
    }
    /**
     * 現在のロールの切り替え
     */
    public function switchRole($role)
    {
        $this->role = $role;
    }
    /**
     * 認証試行を行って得られた権限を取得
     * ロールへの権限設定、切り替えは行わない
     */
    public function authenticate($role, $params)
    {
        return $this->getLoginProvider($role)->authenticate($params);
    }
    /**
     * Requestの認証要求に対して必要な権限がなければResponseを取得
     */
    public function firewall($role, $request, $next)
    {
        return $this->getLoginProvider($role)->firewall($request, $next);
    }

// --

    private $login_providers = array();
    private function getLoginProvider($role)
    {
        if ( ! $this->login_providers[$role]) {
            $role_config = app()->config("auth.roles.".$role);
            $class = $role_config["login"]["class"];
            if ( ! $class && $role === "guest") {
                $class = 'R\Lib\Auth\GuestLogin';
            }
            if ( ! class_exists($class)) {
                report_error("LoginProviderクラスが不正です",array(
                    "role" => $role,
                    "class" => $class,
                    "config" => $config,
                ));
            }
            $this->login_providers[$role] = new $class($role, $role_config["login"]["options"]);
        }
        return $this->login_providers[$role];
    }
}
