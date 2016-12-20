<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Response extends ArrayObject
{
    private $output = null;
    private $report_buffer = array();
    /**
     * 出力内容の設定
     */
    public function output ($output)
    {
        if ($this->hasOutput()) {
            report_warning("設定済みの出力内容を上書きします");
        }
        $this->output = $output;
        app()->end();
    }
    /**
     * 出力内容が設定済みであるかどうか返す
     */
    public function hasOutput ()
    {
        return isset($this->output);
    }
    /**
     * 出力内容を返して消去する
     */
    public function getCleanOutput ()
    {
        $output = $this->output;
        unset($this->output);
        return $output;
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
        ));
    }
    /**
     * URL文字列指定による転送
     */
    public function redirectUrl ($url, $url_params=null)
    {
        $this->output(array(
            "mode" => "redirect",
            "url" => url($url, $url_params),
        ));
    }
    /**
     * エラー応答の設定
     */
    public function error ($message, $error_context=array(), $response_code=500)
    {
        $this->output(array(
            "mode" => "error",
            "message" => $message,
            "error_context" => $error_context,
            "response_code" => $response_code,
        ));
    }
    /**
     * @deprecated
     */
    public function getCleanReportBuffer ()
    {
        return null;
    }
}
