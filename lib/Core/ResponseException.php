<?php
namespace R\Lib\Core;

class ResponseException extends \Exception
{
    protected $response;
    public function __construct ($response)
    {
        parent::__construct();
        $this->response = $response;
    }
    public function getResponse ()
    {
        return $this->response;
    }
}
