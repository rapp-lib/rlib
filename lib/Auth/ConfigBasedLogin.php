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
    {
        if ($auth_table = $this->config["auth_table"]) {
            return table($auth_table)->authenticate($params);
        } elseif ($this->config["accounts"]) {
            foreach ($this->config["accounts"] as $account) {
                if ($params["type"]=="idpw" && strlen($params["login_id"]) && strlen($params["login_pw"])) {
                    if ($account["login_id"]==$params["login_id"] && $account["login_pw"]==$params["login_pw"]) {
                        return $account["priv"] ?: 1;
                    }
                }
            }
        } else {
            report_error("認証方法が設定されていません",array(
                "role" => $this->role,
                "config" => $this->config,
                "authenticate_params" => $params,
            ));
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
