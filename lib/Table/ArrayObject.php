<?php
namespace R\Lib\Table;

use ArrayAccess;

/**
 * SQLの結果
 */
class ArrayObject implements ArrayAccess
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
    public function offsetUnset($key)
    {
        unset($this->storage[$key]);
    }
}
