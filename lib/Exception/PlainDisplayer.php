<?php
namespace R\Lib\Exception;
use Illuminate\Exception\PlainDisplayer as IlluminatePlainDisplayer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PlainDisplayer extends IlluminatePlainDisplayer
{
    public function display(\Exception $e)
    {
        return app()->http->response("error", null, array(
            "status" => $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
            "headers" => $e instanceof HttpExceptionInterface ? $e->getHeaders() : array(),
        ));
    }
}
