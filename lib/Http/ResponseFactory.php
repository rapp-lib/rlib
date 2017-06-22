<?php
namespace R\Lib\Http\Message;
use Zend\Diactoros\Response;

class ResponseFactory
{
    public function factory ($body='php://memory', $options=array())
    {
        $code = isset($options["code"]) ? $options["code"] : 200;
        $headers = isset($options["headers"]) ? $options["headers"] : array();
        $response = new Response($body, $headers, $data);
        return $response;
    }
}
