<?php
namespace R\Lib\Http\Message;
use Zend\Diactoros\ServerRequestFactory;

class ServerRequest extends Zend\Diactoros\ServerRequest
{
    public static function providerFactory ()
    {
        return ServerRequestFactory::fromGlobals();
    }
}
