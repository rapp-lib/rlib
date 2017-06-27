<?php
namespace R\Lib\Controller;
use R\Lib\Core\Contract\Provider;

class ControllerDriver implements Provider
{
    /**
     * Requestに対するメイン処理実行
     */
    public function main ($request)
    {
        $parsed_uri = $request->getWebroot()->parseUri($uri);
        $page_id = $parsed_uri["page_id"];
        list($controller_name, $action_name) = explode('.',$page_id,2);
        $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
        if ( ! class_exists($controller_class)) {
            report_error("URIに対応するControllerクラスの定義がありません",array(
                "uri" => $uri,
                "parsed_uri" => $parsed_uri,
            ));
        }
        $controller = new $controller_class($controller_name, $action_name);
        $controller->execAct();
    }
    /**
     * Requestに対するサブ処理実行
     */
    public function sub ($request, $id)
    {
        $parsed_uri = $request->getWebroot()->parseUri($uri);
        $page_id = $parsed_uri["page_id"];
        list($controller_name, $action_name) = explode('.',$page_id,2);
        $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
        if ( ! class_exists($controller_class)) {
            report_error("URIに対応するControllerクラスの定義がありません",array(
                "uri" => $uri,
                "parsed_uri" => $parsed_uri,
            ));
        }
        $controller = new $controller_class($controller_name, $action_name);
        $controller->execAct();
    }
}
