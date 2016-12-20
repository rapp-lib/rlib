<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 *
 */
class SmartyFunctionInc
{
    /**
     * {{inc path="/element/head.html"}}のサポート
     * @overload
     */
    public static function callback ($params, $smarty)
    {
        $route_name = $params["route"];
        if ( ! $route_name && $params["path"]) {
            $route_name = $params["path"];
        }
        $route = route($route_name);
        // Actionの実行
        $request = new \R\Lib\Webapp\Request($params);
        $response = new \R\Lib\Webapp\Response(array());
        app()->invokeRouteAction($route, $request, $response);
        // テンプレートの読み込み
        $template_file = $route->getFile();
        $smarty->assign((array)$response);
        $output = $smarty->fetch($template_file);
        return $output;
    }
}