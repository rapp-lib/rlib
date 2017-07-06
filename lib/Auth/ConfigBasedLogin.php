<?php
namespace R\Lib\Auth;

class ConfigBasedLogin
{
    protected $role;
    public function __construct($role)
    {
        $this->role = $role;
        $this->config = app()->config("auth.roles.".$this->role.".login.option");
    }
    public function setPriv($priv)
    {
        if ($this->config["persist"]=="session") {
            app()->session("Auth_LoginSession_".$this->role)->priv = $priv;
        }
    }
    public function getPriv()
    {
        if ($this->config["persist"]=="session") {
            return app()->session("Auth_LoginSession_".$this->role)->priv;
        }
        return false;
    }
    public function authenticate($params)
    {
        if (strlen($params["login_id"]) && strlen($params["login_pw"])) {
            foreach ((array)$this->config["accounts"] as $account) {
                if ($account["login_id"]==$params["login_id"] && $account["login_pw"]==$params["login_pw"]) {
                    return $account;
                }
            }
        }
        if ($this->config["table"]) {
            $rs = table($this->config["table"])->login($params)->select();
            if (count($rs)==1) {
                return $rs[0];
            }
        }
        return false;
    }
    public function firewall($request, $next)
    {
        $priv_req = $request->getUri()->getPageAuth()->getPrivReq();
        $priv = $this->getPriv();
        if ($priv_req && ! $priv) {
            if ($this->config["login_request_uri"]) {
                return app()->http->response("redirect", $this->config["login_request_uri"]);
            }
            return app()->http->response("forbidden");
        }
        return $next($request);
    }
}
