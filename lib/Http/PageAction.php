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
        return $this->uri->getPageId() ? $this->getController()->execInc2($request) : null;
    }
    public function getController ()
    {
        $page_id = $this->uri->getPageId();
        $route = $this->uri->getWebroot()->getRouter()->getRouteByPageId($page_id);
        if ( ! $route["page_id"]) {
            report_error("URLに対応するPageIDがありません",array(
                "uri" => $this->uri,
            ));
        }
        $controller = HttpController::getControllerAction($page_id);
        if ( ! $controller) {
            report_error("PageIDに対応するControllerがありません",array(
                "page_id" => $page_id,
            ));
        }
        return $controller;
    }
}
