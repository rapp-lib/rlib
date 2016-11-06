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
        $template_file = $route->getFile();
        $smarty->assign((array)$params["vars"]);
        $output = $smarty->fetch($template_file);
        return $output;
    }
}