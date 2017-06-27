<?php
namespace R\Lib\Route;

use R\Lib\Core\Contract\InvokableProvider;

class RouteManager implements InvokableProvider
{

// -- HttpDriver実装へのMigration

    private $server_request;
    private function migSetCurrentRoute ($webroot_name)
    {
        // config:router.webroot -> http.webrootへの置き換え
        if ( ! app()->config("http.webroot")) {
            $mw_map = array(
                "auth" => '',
                "stored_file_service" => '',
                "json_response_fallback" => '',
                "view_response_fallback" => '',
            );
            foreach (app()->config("router.webroot") as $webroot_name => $raw_config) {
                $webroot_config = array();
                $webroot_config["uri"] = $raw_config["config"]["webroot_url"];
                $webroot_config["asset_catalogs"] = $raw_config["asset"]["catalogs"];
                foreach ($raw_config["routing"] as $page_id => $pattern) {
                    if ($pattern) {
                        $pattern = preg_replace('!/\*$!', '/{__path:.+}', $pattern);
                    }
                    $webroot["routes"][] = array($page_id, $page_path);
                }
                foreach ($raw_config["middleware"] as $mw_name => $mw_check_callback) {
                    $webroot["middlewares"][] = $mw_map[$mw_name];
                }
                app()->config("http.webroot.".$webroot_name, $webroot_config);
            }
        }
        // ServerRequestの初期化
        $this->server_request = app()->http->serve($webroot_name);
    }
    private function migExecCurrentRoute ()
    {
        $app = app();
        $request = $this->server_request;
        $response = $app->http->dispatch($request, function($request){
            $controller = $request->getWebroot()->getController($request->getUri());
            $response = $controller->execAct();
            if ($response) {
                return $response;
            }
            return $request->response("blank", 404);
        });

        return $response;
    }
    public function migGetServerRequest ()
    {
        return $this->server_request;
    }
    public function migMakeResponse ($type, $param=null)
    {
        return $this->server_request->response("blank", 404);
    }
    private function migGetController ($uri)
    {
        return $this->server_request->getWebroot()->getController($uri);
    }
    public function migRenderResponse ($response)
    {
        app()->http->emit($response);
    }

// -- 本体実装

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
    public function setCurrentRoute ($webroot_name, $url)
    {
        $this->current_route = $this->getWebroot($webroot_name)->getRoute("url:".$url);
    }
    /**
     * 現在アクセスされているRouteを取得する
     */
    public function getCurrentRoute ()
    {
        if ( ! isset($this->current_route)) {
            report_error("current_routeが未設定です");
        }
        return $this->current_route;
    }
    /**
     * 現在アクセスされているRouteに関係する処理を実行する
     */
    public function execCurrentRoute ()
    {
        $webroot_name = $this->getWebroot()->getWebrootName();
        $config = (array)app()->config("router.webroot.".$webroot_name);
        // Assetの関連づけ
        if ($catalogs_config = $config["asset"]["catalogs"]) {
            foreach ((array)$catalogs_config as $catalog_path) {
                $route = app()->route($catalog_path);
                app()->asset->loadAssetCatalog(array(
                    "catalog_php" => $route->getFile(),
                    "url" => dirname($route->getUrl()),
                ));
            }
        }
        // Middlewareの起動
        $callback = $this->applyMiddleware(function () {
            return app()->router->getCurrentRoute()->getController()->execAct();
        }, (array)$config["middleware"]);
        return call_user_func($callback);
    }
    /**
     * Routeに対応するControllerインスタンスを取得する
     */
    public function getRouteController ($route)
    {
        $page = $route->getPage();
        if ( ! $page) {
            report_error("Routeに対応するPage設定がありません",array(
                "route" => $route,
            ));
        }
        if ( ! isset($this->controllers[$page])) {
            list($controller_name, $action_name) = explode('.',$page,2);
            $controller_class = 'R\App\Controller\\'.str_camelize($controller_name).'Controller';
            if ( ! class_exists($controller_class)) {
                report_error("Pageに対応するControllerクラスの定義がありません",array(
                    "page" => $page,
                ));
            }
            $this->controllers[$page] = new $controller_class($controller_name, $action_name);
        }
        return $this->controllers[$page];
    }
    /**
     * Middlewareの関連づけ
     */
    protected function applyMiddleware ($callback, $middleware_config)
    {
        $app = app();
        foreach ((array)$middleware_config as $middleware_name => $check) {
            $result = $check($app);
            $middleware_callback = null;
            if ( ! $result) {
                continue;
            } else if (is_callable($result)) {
                $middleware_callback = $result;
            } else {
                $middleware = $app->make("middleware.".$middleware_name);
                $middleware_callback = array($middleware,"handler");
            }
            $callback_next = function () use ($callback, $middleware_callback) {
                return call_user_func($middleware_callback,$callback);
            };
            $callback = $callback_next;
        }
        return $callback;
    }
}
