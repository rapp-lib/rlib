<?php
namespace R\Lib\Extention\SmartyPlugin;

class SmartyModifierCheckUserPriv
{
    public function callback ($priv_req)
    {
        $role = app()->user->getCurrentRole();
        return app()->user->checkCurrentPriv($role, $priv_req);
    }
}
