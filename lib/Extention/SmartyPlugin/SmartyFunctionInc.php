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
        if ( ! $route_name && $params["page"]) {
            $route_name = $params["page"];
        }
        $route = route($route_name);
        $page = $route->getPage();
        $vars = array();
        // Routeに対応する処理の実行
        if ($page) {
            report("IncludeAction実行",array(
                "page" => $page,
            ));
            $controller = $route->getController();
            $controller->execInc();
            $vars = $controller->getVars();
        }
        $request_file = $route->getFile();
        if ( ! file_exists($request_file)) {
            report_warning("incタグの対象となるテンプレートファイルがありません",array(
                "request_file" => $request_file,
                "route" => $route,
            ));
            return;
        }
        // テンプレートの読み込み
        $smarty_clone = clone($smarty);
        $smarty_clone->assign($vars);
        return $smarty_clone->fetch($request_file);
    }
}