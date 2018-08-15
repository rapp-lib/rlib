<?php
namespace R\Lib\Auth;

class UserLoginDriver
{
    /**
     * 現在のロール、明示的に切り替えない限りロールはguest
     */
    private $role = "guest";
    /**
     * 現在のロールの切り替え
     */
    public function switchRole($role)
    {
        $this->role = $role;
    }
    /**
     * 現在のロールと一致する場合のみ、付与されている権限のIDを取得
     */
    public function id($role)
    {
        return $this->priv($role, "id");
    }
    /**
     * 現在のロールと一致する場合のみ、付与されている権限を取得
     */
    public function priv($role, $priv_id)
    {
        return $this->getCurrentPriv($role, $priv_id);
    }

// --

    /**
     * 現在のロールと一致する場合のみ、付与されている権限を取得
     */
    public function getCurrentPriv($role, $priv_id=false)
    {
        if ($role!==$this->role) return false;
        return $this->getPriv($role, $priv_id);
    }
    /**
     * 現在のロールで要求される権限を満たしているか確認
     */
    public function checkCurrentPriv($role, $priv_req)
    {
        if ($role!==$this->role) return false;
        return $this->checkPriv($role, $priv_req);
    }
    /**
     * ログインされている場合のみ、現在のロールを取得
     */
    public function getCurrentRole()
    {
        return $this->getPriv($this->role, "id") ? $this->role : "guest";
    }

// -- LoginProviderの処理

    /**
     * 指定ロールに付与されている権限を取得
     */
    public function getPriv($role, $priv_id=false)
    {
        return $this->getLoginProvider($role)->getPriv($priv_id);
    }
    /**
     * 指定ロールに権限を設定
     * 主にログイン成功直後に使用、ロールは切り替わらない
     */
    public function setPriv($role, $priv)
    {
        $this->getLoginProvider($role)->setPriv($priv);
    }
    /**
     * 指定ロールで要求される権限を満たしているか確認
     */
    public function checkPriv($role, $priv_req)
    {
        return $this->getLoginProvider($role)->checkPriv($priv_req);
    }
    /**
     * 認証制御に対応するTableの取得
     */
    public function getAuthTable($role)
    {
        return $this->getLoginProvider($role)->getAuthTable();
    }
    /**
     * 所有要素の検索処理
     */
    public function onFindMine($role, $table)
    {
        return $this->getLoginProvider($role)->onFindMine($table);
    }
    /**
     * 所有要素の更新処理
     */
    public function onSaveMine($role, $table)
    {
        return $this->getLoginProvider($role)->onSaveMine($table);
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

// -- LoginProviderの管理

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
