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
        if ($priv instanceof \ArrayObject) $priv = $priv->getArrayCopy();
        if ($callback = $this->config["on_logout"]) {
            if ( ! $priv) $priv = $callback($this->getPriv());
        }
        if ($priv && ! is_array($priv)) $priv = (array)$priv;
        if ($this->config["persist"]=="session") {
            app()->session("Auth_LoginSession_".$this->role)->set("priv", $priv);
        }
        $this->priv = $priv;
    }
    public function getPriv($priv_id=false)
    {
        if ($this->config["persist"]=="session") {
            $this->priv = app()->session("Auth_LoginSession_".$this->role)->get("priv");
        }
        return $priv_id===false ? $this->priv : $this->priv[$priv_id];
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
        if ($callback = $this->config["refresh_priv"]) {
            $this->setPriv(call_user_func($callback, $this->getPriv()));
        }
        if ( ! $this->checkPriv($priv_req)) {
            if ($login_request_uri = $this->config["login_request_uri"]) {
                $uri = $request->getUri()->getWebroot()->uri($login_request_uri,
                    array("redirect"=>"".$request->getUri()->withoutAuthorityInWebroot()));
                return app()->http->response("redirect", $uri);
            }
            return app()->http->response("forbidden");
        }
        return $next($request);
    }
    public function getAuthTable()
    {
        return $this->config["auth_table"];
    }
    public function onFindMine($table)
    {
        if ($callback = $this->config["on_find_mine"]) {
            return $callback($table);
        } else {
            return false;
        }
    }
    public function onSaveMine($table)
    {
        if ($callback = $this->config["on_save_mine"]) {
            return $callback($table);
        } else {
            return false;
        }
    }
}
