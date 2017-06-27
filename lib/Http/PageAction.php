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
        $this->getController()->execAct($request);
    }
    public function runInternal ($request)
    {
        $this->getController()->execInc($request);
    }
    public function getController ()
    {
        $page_id = $this->uri->getPageId();
        list($controller_name, $action_name) = explode('.', $page_id, 2);
        $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
        if ( ! class_exists($controller_class)) {
            report_error("Pageに対応するControllerクラスの定義がありません",array(
                "page_id" => $page_id,
                "uri" => "".$this->uri,
            ));
        }
        return new $controller_class($controller_name, $action_name);
    }
}
