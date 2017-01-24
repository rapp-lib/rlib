<?php
namespace R\Lib\Route;

use R\Lib\Core\Contract\InvokableProvider;

class RouteManager implements InvokableProvider
{
    /**
     * @override InvokableProvider
     * Routeインスタンスを取得
     */
    public function invoke ($route_name)
    {
        return $this->getWebroot()->getRoute($route_name);
    }
    private $webroots = array();
    private $current_route = null;
    /**
     * Routeインスタンスを取得
     */
    public function getWebroot ($webroot_name=false)
    {
        if ($webroot_name===false) {
            if ($current_route = $this->getCurrentRoute()) {
                return $current_route->getWebroot();
            } else {
                report_error("CurrentRouteが未設定です");
            }
        }
        if ( ! $this->webroots[$webroot_name]) {
            $this->webroots[$webroot_name] = new Webroot($this, $webroot_name);
        }
        return $this->webroots[$webroot_name];
    }
    /**
     * 現在アクセスされているRouteを設定する
     */
    public function setCurrent ($webroot_name, $route_name)
    {
        $this->current_route = $this->getWebroot($webroot_name)->getRoute($route_name);
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        return $this->current_route;
    }
    /**
     * Routeに対応するActionの処理の実行とテンプレート処理
     */
    public function invokeCurrentRoute ()
    {
        $route = $this->getCurrentRoute();
        $page = $route->getPage();
        if ( ! $page) {
            report_error("リクエストに対応するPage設定がありません",array(
                "route" => $route,
            ),array(
                "response_code" => 404,
            ));
        }
        list($controller_name, $action_name) = explode('.',$page,2);
        $controller_class_name = "R\\App\\Controller\\".str_camelize($controller_name)."Controller";
        if ($auth = $controller_class_name::getAuthenticate()) {
            try {
                $auth_result = auth()->authenticate($auth["access_as"], $auth["priv_required"]);
                if ( ! $auth_result) {
                    report_error("認証エラー時の転送処理が必要",array(
                        "auth_info" => $auth,
                        "route" => $route,
                    ));
                }
            } catch (R\Lib\Auth\AuthRequiredException $e) {
                return $e->getResponse();
            }
        }
        $action_method_name = "act_".$action_name;
        if ( ! method_exists($controller_class_name, $action_method_name)) {
            report_error("Page設定に対応するActionがありません",array(
                "action_method_call" => $controller_class_name."::".$action_method_name,
                "page" => $page,
            ),array(
                "response_code" => 404,
            ));
        }
        // Actionメソッドの呼び出し
        $controller = new $controller_class_name();
        try {
            call_user_func(array($controller,$action_method_name));
        } catch (R\Lib\Core\ResponseException $e) {
            return $e->getResponse();
        }
        // リクエストURLに応じてResponseを作成
        $vars = $controller->getVars();
        $request_file = $route->getFile();
        // .jsonに対するリクエストであればJSONデータを返す
        if (preg_match('!\.json$!',$request_file)) {
            return app()->response()->output(array(
                "data" => json_encode((array)$vars),
                "content_type" => "application/json; charset=utf-8",
            ));
        // .htmlに対するリクエストであればSmartyテンプレートを読み込む
        } else {
            if ( ! file_exists($request_file) && file_exists($request_file."/index.html")) {
                $request_file = $request_file."/index.html";
            }
            if ( ! file_exists($request_file)) {
                report_error("File Not Found", array(
                    "request_file" => $request_file,
                ), array(
                    "response_code" => 404
                ));
            }
            $smarty = new \R\Lib\Smarty\SmartyExtended();
            $smarty->assign((array)$vars);
            $html = $smarty->fetch($request_file);
            return app()->response()->output(array(
                "data" => $html,
                "content_type" => "text/html; charset=utf-8",
            ));
        }
        report_error("Responseがありません",array(
            "route" => $route,
        ));
    }
    /**
     * Pageに対応するIncludeActionの処理の実行
     */
    public function invokeIncludePage ($page)
    {
        list($controller_name, $action_name) = explode('.',$page,2);
        $controller_class_name = "R\\App\\Controller\\".str_camelize($controller_name)."Controller";
        $action_method_name = "inc_".$action_name;
        if ( ! method_exists($controller_class_name, $action_method_name)) {
            return null;
        }
        $controller = new $controller_class_name();
        call_user_func(array($controller,$action_method_name));
        return $controller;
    }
}

