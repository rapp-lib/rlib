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
        $uri = $params["uri"];
        if ( ! $uri && $params["route"]) {
            $uri = "path://".$params["route"];
        }
        if ( ! $uri && $params["path"]) {
            $uri = "path://".$params["path"];
        }
        if ( ! $uri && $params["page"]) {
            $uri = "id://".$params["page"];
        }
        $request = app()->http->getServedRequest();
        $uri = $request->getUri()->getWebroot()->uri($uri);
        // Routeに対応する処理の実行
        $vars = $uri->getPageAction()->runInternal($request);
        $request_file = $uri->getPageFile();
        if ( ! file_exists($request_file)) {
            report_warning("incタグの対象となるテンプレートファイルがありません",array(
                "request_file" => $request_file,
                "uri" => $uri,
            ));
            return;
        }
        // テンプレートの読み込み
        $smarty_clone = clone($smarty);
        $smarty_clone->assign($vars);
        return $smarty_clone->fetch($request_file);
    }
}