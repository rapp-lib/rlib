<?php
namespace R\Lib\Auth;

class ConfigBasedLogin
{
    protected $role;
    protected $priv = null;
    public function __construct($role, $config)
    {
        $this->role = $role;
        $this->config = $config;
    }
    public function setPriv($priv)
    {
        if ($priv && ! is_array($priv)) $priv = (array)$priv;
        if ($this->config["persist"]=="session") {
            app()->session("Auth_LoginSession_".$this->role)->priv = $priv;
        }
        $this->priv = $priv;
    }
    public function getPriv()
    {
        if ($this->config["persist"]=="session") {
            return app()->session("Auth_LoginSession_".$this->role)->priv;
        }
        return $this->priv;
    }
    public function checkPriv($priv_req)
    {
        $priv = $this->getPriv();
        if ($callback = $this->config["check_priv"]) {
            return call_user_func($callback, $priv_req, $priv);
        }
        return ! ($priv_req && ! $priv);
    }
    public function authenticate($params)
    {
        $this->setPriv(false);
        if ($callback = $this->config["authenticate"]) {
            if ($priv = call_user_func($callback, $params)) {
                $this->setPriv($priv);
                return true;
            }
        }
        report_warning("ログインできませんでした",array(
            "role" => $this->role,
            "config" => $this->config,
            "params" => $params,
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
        if ($priv && $callback = $this->config["refresh_priv"]) {
            $priv = call_user_func($callback, $priv);
            $this->setPriv($priv);
        }
        return $next($request);
    }
    public function getAuthTable()
    {
        return $this->config["auth_table"];
    }
}
