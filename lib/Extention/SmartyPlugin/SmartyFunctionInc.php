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
        $vars =(array)$params["vars"];
        $route = route($params["path"] ? $params["path"] : $params["page"]);
        $template_file = $route->getFile();
        $smarty->assign($vars);
        $output = $smarty->fetch($template_file);
        return $output;
    }
}