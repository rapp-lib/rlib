<?php
namespace R\Lib\Core;

use Iterator;
use Serializable;
use ArrayAccess;
use Countable;

/**
 * プロパティに配列の値を持つArrayObject
 * ※arrayにはキャストできないので注意
 */
class ArrayObject implements ArrayAccess, Iterator, Serializable, Countable
{
    protected $array_payload = array();
    protected $array_payload_pos = 0;

    /**
     * 配列そのものを返す
     */
    public function getArrayCopy ()
    {
        return $this->array_payload;
    }

// -- ArrayAccess

    /**
     * @override ArrayAccess
     */
    public function offsetSet ($offset, $value)
    {
        if (is_null($offset)) {
            $this->array_payload[] = $value;
        } else {
            $this->array_payload[$offset] = $value;
        }
    }
    /**
     * @override ArrayAccess
     */
    public function offsetExists ($offset)
    {
        return isset($this->array_payload[$offset]);
    }
    /**
     * @override ArrayAccess
     */
    public function offsetUnset ($offset)
    {
        unset($this->array_payload[$offset]);
    }
    /**
     * @override ArrayAccess
     */
    public function & offsetGet ($offset)
    {
        return $this->array_payload[$offset];
    }

// -- Iterator

    /**
     * @override Iterator
     */
    public function rewind ()
    {
        $this->array_payload_keys = array_keys($this->array_payload);
        $this->array_payload_pos = 0;
    }
    /**
     * @override Iterator
     */
    public function current()
    {
        $key = $this->array_payload_keys[$this->array_payload_pos];
        return $this->offsetGet($key);
    }
    /**
     * @override Iterator
     */
    public function key()
    {
        $key = $this->array_payload_keys[$this->array_payload_pos];
        return $key;
    }
    /**
     * @override Iterator
     */
    public function next()
    {
        ++$this->array_payload_pos;
    }
    /**
     * @override Iterator
     */
    public function valid()
    {
        return isset($this->array_payload_keys[$this->array_payload_pos]);
    }

// -- Serializable

    /**
     * @override Serializable
     */
    public function serialize()
    {
        $data = array(
            "array_payload" => $this->array_payload,
        );
        return serialize($data);
    }
    /**
     * @override Serializable
     */
    public function unserialize($data_str)
    {
        $data = unserialize($data_str);
        $this->array_payload = $data["array_payload"];
    }

// -- Countable

    /**
     * @override Countable
     */
    public function count ()
    {
        return count($this->array_payload);
    }
}