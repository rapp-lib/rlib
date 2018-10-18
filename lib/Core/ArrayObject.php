<?php
namespace R\Lib\Core;

if ( ! interface_exists('\JsonSerializable')) {
    interface JsonSerializable {}
}

class ArrayObject implements \ArrayAccess, \IteratorAggregate, \Serializable, \Countable, \JsonSerializable
{
    protected $storage = array();
    public function offsetGet ($key)
    {
        return $this->storage[$key];
    }
    public function offsetExists ($key)
    {
        return array_key_exists($key, $this->storage) && isset($this->storage[$key]);
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
        return new \ArrayIterator($this->storage);
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
    public function jsonSerialize()
    {
        return $this->storage;
    }

    public function exchangeArray($array)
    {
        $this->storage = $array;
    }
    public function getArrayCopy()
    {
        return $this->storage;
    }
    public function toArray()
    {
        return $this->storage;
    }
    public function has ($keys)
    {
        $ref = & $this->storage;
        if ( ! is_array($keys)) $keys = array($keys);
        foreach ($keys as $k) {
            if ( ! $this->likeArray($ref) || ! array_key_exists($ref, $k)) return false;
            $ref = & $ref[$k];
        }
        return true;
    }
    public function & getRef ($keys)
    {
        $ref = & $this->storage;
        if ( ! is_array($keys)) $keys = array($keys);
        foreach ($keys as $k) {
            if ( ! $this->likeArray($ref)) $ref = array();
            $ref = & $ref[$k];
        }
        return $ref;
    }
    public function get ($keys)
    {
        if ( ! $this->has($keys)) return null;
        return $ref = $this->getRef($keys);
    }
    public function likeArray ($value)
    {
        return is_array($arr) || $arr instanceof \ArrayAccess;
    }
    public function set ($keys, $value)
    {
        $ref = & $this->getRef($keys);
        $ref = $value;
    }
    public function push ($keys, $value)
    {
        $ref = & $this->getRef($keys);
        if ( ! $this->likeArray($ref)) $ref = array();
        array_push($ref, $value);
    }
    public function forget ($keys)
    {
        if ( ! is_array($keys)) $keys = array($keys);
        $top_key = array_pop($keys);
        $ref = $this->getRef($keys);
        unset($ref[$top_key]);
    }
}
