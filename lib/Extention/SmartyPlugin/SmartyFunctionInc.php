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
    public static function callback ($params, $smarty_template)
    {
        $vars =(array)$params["vars"];
        $request_path =$params["path"];
        $request_page =$params["page"];
        $request_file = null;
/*
        $request_file =$params["file"];
        // file="path:..."指定
        if ($request_file && preg_match('!^path:(.*?)$!',$request_file,$match)) {
            $request_path =$match[1];
            $request_file =null;
        } elseif ($request_file && preg_match('!^page:(.*?)$!',$request_file,$match)) {
            $request_page =$match[1];
            $request_file =null;
        }
*/
        // path指定の解決
        if ($request_path) {
            $request_page =path_to_page($request_path);
            $request_file =path_to_file($request_path);
        // page指定の解決
        } elseif ($request_page) {
            $request_path =page_to_path($request_path);
            $request_file =path_to_file($request_path);
        } else {
            report_error("inc Error: Invalid path or page",array(
                "path" =>$request_path,
                "page" =>$request_page,
                "file" =>$request_file,
            ));
            return;
        }
        $template_file = $request_file;
/*
        // 静的ページのStaticIncludeControllerへの対応付け
        if ( ! $request_page && file_exists($request_file)) {
            $request_page ="static_include.index";
        }
        // Routing設定もなくHTMLファイルもない場合は404エラー
        if ( ! $request_page && ! file_exists($request_file)) {
            report_error("inc Error: Route and File NotFound",array(
                "path" =>$request_path,
                "page" =>$request_page,
                "file" =>$request_file,
            ));
            return;
        }
        $controller =raise_action($request_page, array(
            "parent_controller" =>$smarty_template->smarty,
            "parent_smarty_template" =>$smarty_template,
            "vars" =>$vars,
        ));
        // Controller/Action実行エラー
        if ( ! $controller) {
            report_error("inc Routing Error: Controller/Action raise failed",array(
                "path" =>$request_path,
                "page" =>$request_page,
                "file" =>$request_file,
            ));
        }
        return $controller->fetch("file:".$request_file);
*/
        // ControllerActionの処理
        $controller_class_name = null;
        if ($request_page) {
            list($controller_name, $action_name) = explode('.',$request_page,2);
            $controller_class_name = str_camelize($controller_name)."Controller";
            $action_method_name = "act_".$action_name;
            $controller_obj = new $controller_class_name($controller_name,$action_name,$options);
            registry("Response.controller_obj", $controller_obj);
            // Action呼び出し
            if (is_callable(array($controller_obj,$action_method_name))) {
                $controller_obj->before_act();
                $controller_obj->$action_method_name();
                $controller_obj->after_act();
            }
        }
        // Smartyテンプレートの読み込み
        $smarty = new \R\Lib\Smarty\SmartyExtended();
        $smarty->assign((array)request()->response());
        $smarty->assign("request", request());
        $smarty->assign("forms", form()->getRepositry($controller_class_name));
        $smarty->assign($vars);
        $output = $smarty->fetch($template_file);
        return $output;
    }
}