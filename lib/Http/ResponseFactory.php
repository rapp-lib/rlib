<?php
namespace R\Lib\Http;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Stream;

class ResponseFactory
{
    public static function factory ($type, $data=null, $params=array())
    {
        $headers = $params["headers"] ?: array();
        if ($type==="html") {
            return new HtmlResponse($data, $params["status"]?:200, $headers);
        } elseif ($type==="json") {
            return new JsonResponse($data, $params["status"]?:200, $headers);
        } elseif ($type==="redirect") {
            $uri = $data;
            return new RedirectResponse($uri, $params["status"]?:302, $headers);
        } elseif ($type==="empty") {
            return new EmptyResponse($params["status"]?:200, $headers);
        } elseif ($type==="error") {
            return new EmptyResponse($params["status"]?:500, $headers);
        } elseif ($type==="notfound") {
            return new EmptyResponse(404, $headers);
        } elseif ($type==="forbidden") {
            return new EmptyResponse(403, $headers);
        } elseif ($type==="badrequest") {
            return new EmptyResponse(400, $headers);
        } elseif ($type==="readfile") {
            $stream = new Stream($data, 'r');
            return new Response($stream, $params["status"]?:200, $headers);
        } elseif ($type==="stream") {
            if ( ! $data instanceof Stream) $data = new Stream($data, 'r');
            return new Response($data, $params["status"]?:200, $headers);
        } elseif ($type==="data") {
            $stream = new Stream('php://temp', 'wb+');
            $stream->write($data);
            return new Response($stream, $params["status"]?:200, $headers);
        }
    }
}
