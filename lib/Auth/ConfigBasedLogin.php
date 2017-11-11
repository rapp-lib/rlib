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
            app()->session->regenerateId(true);
        }
    }
    public function getPriv()
    {
        if ($this->config["persist"]=="session") {
            return app()->session("Auth_LoginSession_".$this->role)->priv;
        }
        return false;
    }
    public function checkPriv($priv_req)
    {
        $priv = $this->getPriv();
        if ($check_priv = $this->config["check_priv"]) {
            return call_user_func($check_priv, $priv_req, $priv);
        } else {
            return ! ($priv_req && ! $priv);
        }
    }
    public function authenticate($params)
    {
        if ($accounts = $this->config["accounts"]) {
            foreach ($accounts as $account) {
                if ($params["type"]=="idpw" && strlen($params["login_id"]) && strlen($params["login_pw"])) {
                    if ($account["login_id"]==$params["login_id"] && $account["login_pw"]==$params["login_pw"]) {
                        return $account["priv"] ?: 1;
                    }
                }
            }
        }
        if ($auth_table = $this->config["auth_table"]) {
            if ($priv = table($auth_table)->authenticate($params)) {
                return $priv;
            }
        }
        report_warning("ログインできませんでした",array(
            "role" => $this->role,
            "config" => $this->config,
            "authenticate_params" => $params,
        ));
        return false;
    }
    public function firewall($request, $next)
    {
        $priv_req = $request->getUri()->getPageAuth()->getPrivReq();
        $priv = $this->getPriv();
        if ($priv_req && ! $priv) {
            if ($login_request_uri = $this->config["login_request_uri"]) {
                $uri = $request->getUri()->getWebroot()->uri($login_request_uri,
                    array("redirect"=>"".$request->getUri()->withoutAuthorityInWebroot()));
                return app()->http->response("redirect", $uri);
            }
            return app()->http->response("forbidden");
        } elseif ( ! $this->checkPriv($priv_req)) {
            return app()->http->response("forbidden");
        }
        return $next($request);
    }
    public function getAuthTable()
    {
        return $this->config["auth_table"];
    }
}
