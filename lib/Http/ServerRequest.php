<?php
namespace R\Lib\Http;

class ServerRequest extends \Zend\Diactoros\ServerRequest
{
    public function getWebroot()
    {
        return $this->getUri()->getWebroot();
    }
}
