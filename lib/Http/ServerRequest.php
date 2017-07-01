<?php
namespace R\Lib\Http;

class ServerRequest extends \Zend\Diactoros\ServerRequest implements \ArrayAccess , \IteratorAggregate
{
    public function getWebroot()
    {
        return $this->getAttribute("webroot");
    }

// -- ArrayAccess,IteratorAggregateの実装

    public function offsetExists ($offset)
    {
        $values = $this->getAttribute("values");
        return $values ? isset($values[$offset]) : false;
    }
    public function offsetGet ($offset)
    {
        $values = $this->getAttribute("values");
        return $values ? $values[$offset] : null;
    }
    public function offsetSet ($offset, $value)
    {
        return;
    }
    public function offsetUnset ($offset)
    {
        return;
    }
    public function getIterator ()
    {
        $values = $this->getAttribute("values");
        return $values ?: new \ArrayIterator(array());
    }

// --

    public function __report ()
    {
        return array(
            "uri" => $this->getUri(),
            "values" => $this->getAttribute("values"),
        );
    }
}
