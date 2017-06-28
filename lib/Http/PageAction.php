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
        return $this->getController($request)->execAct2();
    }
    public function runInternal ($request)
    {
        return $this->getController($request)->execInc2();
    }
    private function getController ($request=null)
    {
        $page_id = $this->uri->getPageId();
        return R\Lib\Controller\HttpController::getControllerAction($page_id, $request);
    }
}
