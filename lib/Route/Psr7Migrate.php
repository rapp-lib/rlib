<?php
namespace R\Lib\Router;

use R\Lib\Core\Contract\InvokableProvider;

class Psr7Migrate implements InvokableProvider
{
// -- HttpDriver実装へのMigration

    private $server_request;
    public function migSetCurrentRoute ($set_webroot_name)
    {
        $app = app();
        // 設定を router.webroot -> http.webroot に置き換え
        if ( ! app()->config("http.webroot")) {
            $mw_map = array(
                "auth" => '',
                "stored_file_service" => '',
                "json_response_fallback" => '',
                "view_response_fallback" => '',
            );
            foreach ($app->config("router.webroot") as $webroot_name => $raw_config) {
                $webroot_config = array();
                $webroot_config["base_uri"] = $raw_config["config"]["webroot_url"];
                $webroot_config["asset_catalogs"] = $raw_config["asset"]["catalogs"];
                foreach (array_dot($raw_config["routing"]) as $page_id => $pattern) {
                    if ($pattern) {
                        $pattern = preg_replace('!/\*$!', '/{__path:.+}', $pattern);
                    }
                    $webroot["routes"][] = array($page_id, $pattern);
                }
                foreach ($raw_config["middleware"] as $mw_name => $mw_check_callback) {
                    $webroot["middlewares"][] = $mw_map[$mw_name];
                }
                $app->config("http.webroot.".$webroot_name, $webroot_config);
            }
        }
        // ServerRequestの初期化
        $this->server_request = $app->http->serve($set_webroot_name);
        return $this->migGetRoute($this->server_request->getUri());
    }
    public function migExecCurrentRoute ()
    {
        $app = app();
        $request = $this->server_request;
        $response = $request->dispatch(function($request){
            $response = $request->getUri()->getPageAction()->run($request);
            return $response;
        });
        return $response;
    }
    public function migGetServerRequest ()
    {
        return $this->server_request;
    }
    public function migGetRoute ($uri)
    {
        return $this->server_request;
    }

    private function migRespond ($response)
    {
        app()->http->emit($response);
    }
    private function migOutput ($ouput)
    {
        return app()->http->response($type, $params);
    }
}