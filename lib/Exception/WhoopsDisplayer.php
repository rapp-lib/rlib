<?php
namespace R\Lib\Exception;
use Illuminate\Exception\WhoopsDisplayer as IlluminateWhoopsDisplayer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WhoopsDisplayer extends IlluminateWhoopsDisplayer
{
    public function display(\Exception $e)
    {
        $html = $this->whoops->handleException($e);
        return app()->http->response("html", $html, array(
            "status" => $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
            "headers" => $e instanceof HttpExceptionInterface ? $e->getHeaders() : array(),
        ));
    }
}
