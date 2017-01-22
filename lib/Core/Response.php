<?php
namespace R\Lib\Core;

abstract class Response
{
    protected $output = null;
    public function __construct ($output=null)
    {
        $this->output = $output;
    }
    public function raise ()
    {
        throw new ResponseException($this);
    }
    abstract public function render ();

// -- 出力Response生成

    /**
     * 出力内容の設定
     */
    public function output ($output)
    {
        $class = get_class($this);
        return new $class($output);
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
            "mode" => "redirect",
            "url" => $route->getUrl(),
        ))->raise();
    }
    /**
     * URL文字列指定による転送
     */
    public function redirectUrl ($url, $url_params=array())
    {
        $this->output(array(
            "mode" => "redirect",
            "url" => url($url, $url_params),
        ))->raise();
    }
    /**
     * エラー応答の設定
     */
    public function error ($message, $error_context=array(), $error_options=array())
    {
        $this->output(array(
            "mode" => "error",
            "message" => $message,
            "error_context" => $error_context,
            "response_code" => $error_options["response_code"],
        ))->raise();
    }
}
