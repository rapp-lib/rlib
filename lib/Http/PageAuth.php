<?php
namespace R\Lib\Http;

class PageAuth
{
    protected $uri;
    public function __construct ($uri)
    {
        $this->uri = $uri;
        $route = $this->uri->getRoute();
        // Controllerでの指定で補完
        $controller = $this->uri->getPageAction()->getController();
        $this->role = $route["auth"]["role"] ?: "guest";
        if ($controller && $this->role==="guest") {
            $this->role = $controller->getAccessRoleName();
        }
        $this->priv_req = $route["auth"]["priv_req"];
        if ($controller && ! isset($this->priv_req)) {
            $this->priv_req = $controller->getPrivRequired();
        }
        // 指定が無ければguest
        if ( ! isset($this->role)) {
            $this->role = "guest";
        }
        // guestは認証不可
        if ($this->role==="guest" && isset($this->priv_req)) {
            $this->priv_req = false;
        }
        // priv_reqはホワイトリスト方式
        if ($this->role!=="guest" && ! isset($this->priv_req)) {
            $this->priv_req = true;
        }
    }
    public function getRole ()
    {
        return $this->role;
    }
    public function getPrivReq ()
    {
        return $this->priv_req;
    }
}
