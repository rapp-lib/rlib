<?php
namespace R\Lib\Http;
use Zend\Diactoros\Stream;

class ResponseFactory
{
    private static $fallback_status_codes = array(
        "empty" => 204,
        "redirect" => 302,
        "badrequest" => 400,
        "forbidden" => 403,
        "notfound" => 404,
        "error" => 500,
    );
    public static function factory ($type, $data=null, $params=array())
    {
        //self::debug($type);
        $headers = $params["headers"] ?: array();
        $fallback_status_code = self::$fallback_status_codes[$type];
        $status_code = $params["status"] ?: $fallback_status_code ?: 200;
        // 応答データの補完
        if ($fallback_status_code && $data===null) {
            if ($data = self::getFallbackHtml($type)) {
                $data = self::createBody($data);
            } else {
                $data = new Stream('php://temp', 'r');
            }
        }
        if ($type==="html") {
            $data = self::createBody($data);
            $headers = self::injectContentType('text/html', $headers);
        } elseif ($type==="json") {
            $data = self::createBody(json_encode($data, 15));
            $headers = self::injectContentType('application/json', $headers);
        } elseif ($type==="data") {
            $data = self::createBody($data);
        } elseif ($type==="readfile" || $type==="stream") {
            if ($params["range"]) {
                $size = filesize($data);
                // 要求された開始位置と終了位置を取得
                list($start, $end) = sscanf($params["range"], "bytes=%d-%d");
                // 終了位置が指定されていない場合(適当に1000000bytesづつ出す)
                if(empty($end)) $end = $start + 1000000 - 1;
                // 終了位置がファイルサイズを超えた場合
                if($end >= ($size-1)) $end = $size - 1;
                // 実際に送信するコンテンツ長: 終了位置 - 開始位置 + 1
                $size = $end - $start + 1;
                // ファイルポインタの開始位置からコンテンツ長だけ出力
                if($size) {
                    // 部分コンテンツであることを伝える
                    $status_code = 206;
                    $headers["Content-Range"] = "bytes {$start}-{$end}/{$size}";
                    $headers["Content-Length"] = "{$size}";
                    // header("Etag: \"{$etag}\"");
                    $stream = fopen($data, "rb");
                    fseek($stream, $start);
                    $data = new \Zend\Diactoros\Stream('php://temp', 'wb+');
                    $data->write(fread($stream, $size));
                }
            }
            if ( ! $data instanceof Stream) $data = new Stream($data, 'r');
        } elseif ($type==="redirect") {
            $headers['location'] = array((string)$data);
            $data = 'php://temp';
        } elseif ($type==="empty") {
            $data = new Stream('php://temp', 'r');
        }
        return new Response($data, $status_code, $headers);
    }
    public static function createBody ($html)
    {
        if ($html instanceof StreamInterface) {
            return $html;
        }
        $body = new Stream('php://temp', 'wb+');
        $body->write($html);
        return $body;
    }
    private function injectContentType($contentType, array $headers)
    {
        $hasContentType = array_reduce(array_keys($headers), function ($carry, $item) {
            return $carry ?: (strtolower($item) === 'content-type');
        }, false);
        if ( ! $hasContentType) $headers['content-type'] = array($contentType);
        return $headers;
    }
    private static function getFallbackHtml ($type)
    {
        $error_file = constant("R_APP_ROOT_DIR")."/resources/error/".$type.".php";
        if ( ! file_exists($error_file)) {
            $error_file = constant("R_LIB_ROOT_DIR")."/assets/error/".$type.".php";
        }
        if ( ! file_exists($error_file)) return null;
        ob_start();
        include($error_file);
        return ob_get_clean();
    }
    public static function debug($type)
    {
        foreach (debug_backtrace() as $k1=>$v1){
            if (is_object($v1)) $v1 = (array)$v1; 
            if (!is_array($v1)) $debug[$k1] = $v1;
            else foreach ($v1 as $k2=>$v2) {
            if (is_object($v2)) $v2 = (array)$v2;
                if (!is_array($v2)) $debug[$k1][$k2] = $v2;
                else foreach ($v2 as $k3=>$v3){
                    if (is_object($v3)) $v3 = (array)$v3;
                    if (!is_array($v3)) $debug[$k1][$k2][$k3] = $v3;
                    else foreach ($v3 as $k4=>$v4){
                        if (is_object($v4)) $v4 = (array)$v4;
                        if (!is_array($v4)) $debug[$k1][$k2][$k3][$k4] = $v4;
                    }
                }
            }
        }
        file_put_contents(R_APP_ROOT_DIR."/tmp/debug_test".$type.".txt", date("H:i:s NF\n").print_r($debug,1));
    }
}
