<?php
namespace R\Lib\Http;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\EmptyResponse;

class ResponseFactory
{
    public static function factory ($type, $data=null, $params=array())
    {
        if ($type==="html") {
            return new HtmlResponse($data, $params["status"]?:200, $params["headers"]?:array());
        } elseif ($type==="json") {
            return new JsonResponse($data, $params["status"]?:200, $params["headers"]?:array());
        } elseif ($type==="redirect") {
            $uri = $data;
            return new RedirectResponse($uri, $params["status"]?:302, $params["headers"]?:array());
        } elseif ($type==="empty") {
            return new EmptyResponse($params["status"]?:200, $params["headers"]?:array());
        } elseif ($type==="error") {
            return new EmptyResponse($params["status"]?:500, $params["headers"]?:array());
        } elseif ($type==="notfound") {
            return new EmptyResponse($params["status"]?:404, $params["headers"]?:array());
        } elseif ($type==="forbidden") {
            return new EmptyResponse($params["status"]?:403, $params["headers"]?:array());
        } elseif ($type==="readfile") {
            $data = new Stream($data, 'r');
            return new Response($data, $params["status"]?:200, $params["headers"]?:array());
        } elseif ($type==="stream") {
            return new Response($data, $params["status"]?:200, $params["headers"]?:array());
        } elseif ($type==="data") {
            $stream = new Stream('php://temp', 'wb+');
            $stream->write($data);
            return new Response($stream, $params["status"]?:200, $params["headers"]?:array());
        }
    }
}
