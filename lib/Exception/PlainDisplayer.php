<?php
namespace R\Lib\Exception;
use Illuminate\Exception\PlainDisplayer as IlluminatePlainDisplayer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PlainDisplayer extends IlluminatePlainDisplayer
{
    public function display(\Exception $exception)
    {
        // $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        // $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : array();
        return app()->http->response("error");
    }
}
