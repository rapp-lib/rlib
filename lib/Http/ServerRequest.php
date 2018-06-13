<?php
namespace R\Lib\Http;

class ServerRequest extends \Zend\Diactoros\ServerRequest
{
    public function isAjax ()
    {
        return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
    }
    public function wantsJson ()
    {
        return 'application/json' == $this->getHeaderLine('Accept');
    }
    public function getRequestFormat ()
    {
        //
    }
    public function getInputValues ()
    {
        return $this->getAttribute(InputValues::ATTRIBUTE_INDEX);
    }
}
