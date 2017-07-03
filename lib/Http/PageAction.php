<?php
namespace R\Lib\Http;

class PageAction
{
    protected $uri;
    public function __construct ($uri)
    {
        $this->uri = $uri;
    }
    public function run ($request)
    {
        return $this->getController()->execAct2($request);
    }
    public function runInternal ($request)
    {
        return $this->getController()->execInc2($request);
    }
    public function getController ()
    {
        $page_id = $this->uri->getPageId();
        return \R\Lib\Controller\HttpController::getControllerAction($page_id);
    }
}
