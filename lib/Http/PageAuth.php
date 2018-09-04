<?php
namespace R\Lib\Http;

class PageAuth
{
    protected $uri;
    public function __construct ($uri)
    {
        $this->uri = $uri;
        $route = $this->uri->getRoute();
        $this->role = $route["auth"]["role"] ?: "guest";
        $this->priv_req = $route["auth"]["priv_req"];
        // guestは認証不可
        if ($this->role==="guest" && isset($this->priv_req)) $this->priv_req = false;
        // priv_reqはホワイトリスト方式
        // if ($this->role!=="guest" && $this->priv_req!==false) $this->priv_req = true;
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
