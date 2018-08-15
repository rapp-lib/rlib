<?php
namespace R\Lib\Auth;

class GuestLogin
{
    public function __construct($role)
    {
    }
    public function setPriv($priv)
    {
    }
    public function getPriv($priv_id=false)
    {
        return false;
    }
    public function checkPriv($priv_req)
    {
        return true;
    }
    public function getAuthTable()
    {
        return null;
    }
    public function onFindMine()
    {
        return false;
    }
    public function onSaveMine()
    {
        return false;
    }
    public function authenticate($params)
    {
        return false;
    }
    public function firewall($request, $next)
    {
        return $next($request);
    }
}
