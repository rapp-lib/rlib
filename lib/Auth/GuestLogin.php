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
    public function getPriv()
    {
        return false;
    }
    public function checkPriv($priv_req)
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
