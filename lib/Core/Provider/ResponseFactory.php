<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\Provider;

class ResponseFactory implements Provider
{
    /**
     * 出力内容の設定
     */
    public function output ($output)
    {
        if ( ! isset($output["type"])) {
            $output["type"] = "unknown";
        }
        return app()->make("response.".$output["type"], array($output));
    }
    /**
     * 転送応答の設定
     */
    public function redirect ($route, $url_params=null)
    {
        $route = is_string($route) ? route($route) : $route;
        if (isset($url_params)) {
            $route->setUrlParams($url_params);
        }
        $this->output(array(
            "type" => "redirect",
            "url" => $route->getUrl(),
        ))->raise();
    }
    /**
     * URL文字列指定による転送
     */
    public function redirectUrl ($url, $url_params=array())
    {
        $this->output(array(
            "type" => "redirect",
            "url" => url($url, $url_params),
        ))->raise();
    }
    /**
     * エラー応答の設定
     */
    public function error ($message, $error_context=array(), $error_options=array())
    {
        $this->output(array(
            "type" => "error",
            "message" => $message,
            "error_context" => $error_context,
            "response_code" => $error_options["response_code"],
        ))->raise();
    }
}
