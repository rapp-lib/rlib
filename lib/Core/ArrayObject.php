<?php
namespace R\Lib\Core;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use Serializable;
use Countable;

class ArrayObject implements ArrayAccess, IteratorAggregate, Serializable, Countable
{
    protected $storage = array();
    public function offsetGet ($key)
    {
        return $this->storage[$key];
    }
    public function offsetExists ($key)
    {
        return array_key_exists($key, $this->storage);
    }
    public function offsetSet ($key, $value)
    {
        $this->storage[$key] = $value;
    }
    public function offsetUnset ($key)
    {
        unset($this->storage[$key]);
    }
    public function getIterator ()
    {
        return new ArrayIterator($this->storage);
    }
    public function serialize ()
    {
        return serialize($this->storage);
    }
    public function unserialize ($serialized)
    {
        $this->storage = unserialize($serialized);
    }
    public function count ()
    {
        return count($this->storage);
    }
    public function toArray()
    {
        return $this->storage;
    }
}
