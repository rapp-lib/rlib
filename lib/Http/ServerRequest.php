<?php
namespace R\Lib\Http;
use Zend\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends \Zend\Diactoros\ServerRequest implements \ArrayAccess , \IteratorAggregate
{
    protected $webroot;
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
            parent::__construct($server, $files, $uri, $method, 'php://input', $headers);
            $this->_cookieParams = $request["cookies"] ?: $_COOKIE;
            $this->_queryParams = $request["query"] ?: $_GET;
            $this->_parsedBody = $request["body"] ?: $_POST;
        // 一般的なServerRequestInterfaceをもとに初期化
        } elseif ($request instanceof ServerRequestInterface) {
            $server  = $request->getServerparams();
            $files   = $request->getUploadedFiles();
            $headers = $request->getHeaders();
            $uri     = $request->getUri();
            $method  = $request->getMethod();
            parent::__construct($server, $files, $uri, $method, 'php://input', $headers);
            $this->_cookieParams = $request->getCookieParams();
            $this->_queryParams = $request->getQueryParams();
            $this->_parsedBody = $request->getParsedBody();;
        } else {
            report_error("不正な引数");
        }
        $this->webroot->updateByRequest($this, parent::getUri());
    }
    public function __call ($func, $args)
    {
        return call_user_func_array(array($this->webroot, $func), $args);
    }
    public function getWebroot()
    {
        return $this->webroot;
    }
    public function dispatch ($deligate)
    {
        $stack = $this->webroot->getMiddlewareStack();
        $stack[] = $deligate;
        $dispatcher = new \mindplay\middleman\Dispatcher($stack);
        $response = $dispatcher->dispatch($this);
        return $response;
    }
    public function getUri()
    {
        if ( ! isset($this->uri)) {
            $this->uri = $this->webroot->uri(parent::getUri());
        }
        return $this->uri;
    }

// -- ArrayAccess,IteratorAggregateの実装

    protected $request_values;
    public function offsetExists ($offset)
    {
        $this->initValue();
        return isset($this->request_values[$offset]);
    }
    public function offsetGet ($offset)
    {
        $this->initValue();
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
    public function getIterator ()
    {
        $this->initValue();
        return new \ArrayIterator($this->request_values);
    }
    private function initValue ()
    {
        if ( ! isset($this->request_values)) {
            // Request値配列の構築
            $this->request_values = array_merge(
                (array)$this->getQueryParams(),
                (array)$this->getParsedBody(),
                (array)$this->getUri()->getEmbedParams()
            );
            $this->sanitizeRecursive($this->request_values);
        }
    }
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

// -- parentでPrivateで設定できない値

    private $_cookieParams = array();
    private $_parsedBody;
    private $_queryParams = array();
    public function getCookieParams()
    {
        return $this->_cookieParams;
    }
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->_cookieParams = $cookies;
        return $new;
    }
    public function getQueryParams()
    {
        return $this->_queryParams;
    }
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->_queryParams = $query;
        return $new;
    }
    public function getParsedBody()
    {
        return $this->_parsedBody;
    }
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->_parsedBody = $data;
        return $new;
    }

// --

    public function __report ()
    {
        $this->initValue();
        return array(
            "uri" => $this->getUri(),
            "values" => $this->request_values,
        );
    }
}
