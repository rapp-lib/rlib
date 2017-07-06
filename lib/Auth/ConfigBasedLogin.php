<?php
namespace R\Lib\Auth;

class ConfigBasedLogin
{
    protected $role;
    public function __construct($role, $config)
    {
        $this->role = $role;
        $this->config = $config;
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
    {report( array($this->config["accounts"],$params));
        if (strlen($params["login_id"]) && strlen($params["login_pw"])) {
            foreach ((array)$this->config["accounts"] as $account) {
                if ($account["login_id"]==$params["login_id"] && $account["login_pw"]==$params["login_pw"]) {
                    return $account;
                }
            }
        }
        if ($table = $this->config["table"]) {
            return table($table)->authenticate($params);
        }
        return false;
    }
    public function firewall($request, $next)
    {
        $priv_req = $request->getUri()->getPageAuth()->getPrivReq();
        $priv = $this->getPriv();
        if ($priv_req && ! $priv) {
            if ($login_request_uri = $this->config["login_request_uri"]) {
                $uri = $request->getUri()->getWebroot()->uri($login_request_uri);
                return app()->http->response("redirect", $uri);
            }
            return app()->http->response("forbidden");
        }
        return $next($request);
    }
}
