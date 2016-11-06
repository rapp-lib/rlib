<?php
namespace R\Lib\Webapp;

use ArrayObject;

class Response extends ArrayObject
{
    private static $instance = null;

    private $output = null;
    private $report_buffer = array();

    /**
     * インスタンスを取得
     */
    public static function getInstance ()
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Response;
        }
        return self::$instance;
    }
    /**
     * 出力内容の設定
     */
    public function output ($output)
    {
        if ($this->hasOutput()) {
            report_warning("設定済みの出力内容を上書きします",array(
            ));
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
            "response_code_msg" => self::$response_code_msg[$response_code],
        ));
    }
    /**
     * Reportバッファへの追記
     */
    public function writeReportBuffer ($data)
    {
        $this->report_buffer[] = $data;
    }
    /**
     * Reportバッファの内容の取得
     */
    public function getCleanReportBuffer ()
    {
        $report_buffer = implode('',$this->report_buffer);
        $this->report_buffer = array();
        return $report_buffer;
    }

    /**
     * RFCに定義されている応答コードに対応するメッセージ
     */
    private static $response_code_msg =array(
        // 1xx Informational 情報
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        // 2xx Success 成功
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-Status",
        226 => "IM Used",
        // 3xx Redirection リダイレクション
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "(Unused)",
        307 => "Temporary Redirect",
        // 4xx Client Error クライアントエラー
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",
        422 => "Unprocessable Entity",
        423 => "Locked",
        424 => "Failed Dependency",
        426 => "Upgrade Required",
        // 5xx Server Error サーバエラー
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        506 => "Variant Also Negotiates",
        507 => "Insufficient Storage",
        509 => "Bandwidth Limit Exceeded",
        510 => "Not Extended",
    );
}
