<?php
namespace R\Lib\Laravel\Auth;

class UserLoginDriver
{
    public function switchRole($role)
    {
        report_error("deprecated");
        // $this->role = $role;
    }
    /**
     * 現在のロールと一致する場合のみ、付与されている権限のIDを取得
     */
    public function id($role)
    {
        if ($this->getRequestRole() !== $role) return null;
        return $this->getGuard()->id();
        //return $this->priv($role, "id");
    }
    /**
     * 現在のロールと一致する場合のみ、付与されている権限を取得
     */
    public function priv($role, $priv_id)
    {
        report_error("deprecated");
        // return $this->getCurrentPriv($role, $priv_id);
    }

// --

    /**
     * 現在のロールと一致する場合のみ、付与されている権限を取得
     */
    public function getCurrentPriv($role, $priv_id=false)
    {
        report_error("deprecated");
        // if ($this->getRequestRole() != $role) return null;
        // return $this->getGuard()->user()->__get($priv_id);
        // if ($role!==$this->role) return false;
        // return $this->getPriv($role, $priv_id);
    }
    /**
     * 現在のロールで要求される権限を満たしているか確認
     */
    public function checkCurrentPriv($role, $priv_req)
    {
        report_error("deprecated");
        // if ($role!==$this->role) return false;
        // return $this->checkPriv($role, $priv_req);
    }
    /**
     * ログインされている場合のみ、現在のロールを取得
     */
    public function getCurrentRole()
    {
        report_error("deprecated");
        // return $this->getPriv($this->role, "id") ? $this->role : "guest";
    }

// -- LoginProviderの処理

    /**
     * 指定ロールに付与されている権限を取得
     */
    public function getPriv($role, $priv_id=false)
    {
        report_error("deprecated");
        // return $this->getLoginProvider($role)->getPriv($priv_id);
    }
    /**
     * 指定ロールに権限を設定
     * 主にログイン成功直後に使用、ロールは切り替わらない
     */
    public function setPriv($role, $priv)
    {
        // 自動生成のログアウトのコードに入っている
        if ($priv===false) \Auth::guard($role)->logout();
        // $this->getLoginProvider($role)->setPriv($priv);
        // report_info("Auth setPriv", array("role"=>$role, "priv"=>$priv), "Auth");
    }
    /**
     * 指定ロールで要求される権限を満たしているか確認
     */
    public function checkPriv($role, $priv_req)
    {
        report_error("deprecated");
        // return $this->getLoginProvider($role)->checkPriv($priv_req);
    }
    /**
     * 認証制御に対応するTableの取得
     */
    public function getAuthTable($role)
    {
        report_error("deprecated");
        // return $this->getLoginProvider($role)->getAuthTable();
    }
    /**
     * 所有要素の検索処理
     */
    public function onFindMine($role, $table)
    {
        return false;
        if ($callback = $this->config["on_find_mine"]) {
            return $callback($table);
        } else {
            return false;
        }
        // return $this->getLoginProvider($role)->onFindMine($table);
    }
    /**
     * 所有要素の更新処理
     */
    public function onSaveMine($role, $table)
    {
        return false;
        if ($callback = $this->config["on_save_mine"]) {
            return $callback($table);
        } else {
            return false;
        }
        // return $this->getLoginProvider($role)->onSaveMine($table);
    }
    /**
     * 認証試行を行って得られた権限を取得
     * ロールへの権限設定、切り替えは行わない
     */
    public function authenticate($role, $params)
    {
        return \Auth::guard($role)->attempt($params);
        // report_info("Auth authenticate", array("role"=>$role, "priv"=>$params), "Auth");
        // return $this->getLoginProvider($role)->authenticate($params);
    }
    /**
     * Requestの認証要求に対して必要な権限がなければResponseを取得
     */
    public function firewall($role, $request, $next)
    {
        report_error("deprecated");
        // return $this->getLoginProvider($role)->firewall($request, $next);
    }

// -- LoginProviderの管理

    private function getGuard($role)
    {
        return \Auth::guard($role);
    }
    private function getRoleUserProvider($role)
    {
        $provider = $this->getGuard()->getProvider();
        if ( ! $provider instanceof \R\Lib\Laravel\Auth\UserProvider\RoleUserProvider) {
            throw new InvalidArgumentException("Invalid role: ".$role);
        }
        return $provider;
    }
    private function getRequestRole()
    {
        return app("laravelizer")->getRouteRole(app("request")->route());
    }
}
