<?php
namespace R\Lib\Http;

class ResponseException extends \Exception
{
    public function __construct ($response)
    {
        $this->response = $response;
        parent::__construct("Http response");
    }
    public function render ()
    {
        return $this->response;
    }
}
