<?php
namespace R\Lib\Http;
use Zend\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends \Zend\Diactoros\ServerRequest implements \ArrayAccess
{
    protected $webroot;
    protected $parsed_request_uri;
    protected $request_values;
    public function __construct ($webroot, $request)
    {
        // Webrootの設定
        $this->webroot = $webroot;
        // スーパーグローバル変数をもとに初期化（parent::__construct）
        if (is_array($request)) {
            $server  = ServerRequestFactory::normalizeServer($request["server"] ?: $_SERVER);
            $files   = ServerRequestFactory::normalizeFiles($request["files"] ?: $_FILES);
            $headers = ServerRequestFactory::marshalHeaders($server);
            $uri     = ServerRequestFactory::marshalUriFromServer($server, $headers);
            $method  = ServerRequestFactory::get('REQUEST_METHOD', $server, 'GET');
            $cookies = $request["cookies"] ?: $_COOKIE;
            $query_params = $request["query"] ?: $_GET;
            $parsed_body = $request["body"] ?: $_POST;
            $uri = $this->webroot->uri($uri);
            parent::__construct($server, $files, $uri, $method,
                'php://input', $headers, $cookies, $query_params, $parsed_body);
        // 一般的なServerRequestInterfaceをもとに初期化
        } elseif ($request instanceof ServerRequestInterface) {
            $server  = $request->getServerparams();
            $files   = $request->getUploadedFiles();
            $headers = $request->getHeaders();
            $uri     = $request->getUri();
            $method  = $request->getMethod();
            $cookies = $request->getCookieParams();
            $query_params = $request->getQueryParams();
            $parsed_body = $request->getParsedBody();
            $uri = $this->webroot->uri($uri);
            parent::__construct($server, $files, $uri, $method,
                'php://input',$headers, $cookies, $query_params, $parsed_body);
        } else {
            report_error("不正な引数");
        }
        // Request値配列の構築
        $this->request_values = array_merge($this->getQueryParams(),
            $this->getParsedBody(), $this->getUri()->getEmbedParams());
        $this->sanitizeRecursive($this->request_values);
    }
    public function __call ($func, $args)
    {
        return call_user_func_array(array($this->webroot, $func), $args);
    }
    public function getWebroot()
    {
        return $this->webroot;
    }
    public function dispatch ($root_deligate)
    {
        $response = $this->getWebroot()->getMiddlewareDispatcher()->dispatch($this, $root_deligate);
        return $response;
    }

// -- ArrayAccessの実装

    public function offsetExists ($offset)
    {
        return isset($this->request_values[$offset]);
    }
    public function offsetGet ($offset)
    {
        return $this->request_values[$offset];
    }
    public function offsetSet ($offset, $value)
    {
        return;
    }
    public function offsetUnset ($offset)
    {
        return;
    }

// -- @private

    private function sanitizeRecursive ( & $arr)
    {
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $this->sanitizeRecursive($arr[$key]);
            } else {
                $arr[$key] = htmlspecialchars($val, ENT_QUOTES);
            }
        }
    }
}
