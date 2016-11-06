<?php
namespace R\Lib\Auth;

interface Authenticator
{
    /**
     * 認証情報の取得
     * @return array array("access_as"=>$role_name, "required"=>$privs_required)
     */
    public static function getAuthenticate ();
}
