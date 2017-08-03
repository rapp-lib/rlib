<?php
namespace R\Lib\Extention\SmartyPlugin;

class SmartyModifierCheckUserPriv
{
    public function callback ($priv_req, $role=null)
    {
        $role = isset($role) ? $role : app()->user->getCurrentRole();
        return app()->user->checkCurrentPriv($role, $priv_req);
    }
}
