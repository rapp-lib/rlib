<?php
namespace R\Lib\Enum;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class EnumValueRepositry implements ArrayAccess, IteratorAggregate
{
    private $values_name;
    private $retreived = false;
    private $values = array();
    public function __construct($values_name)
    {
        $this->values_name = $values_name;
    }
    public function offsetExists($key)
    {
        if ( ! isset($this->values[$key])) $this->retreive(array($key));
        return isset($this->values[$key]);
    }
    public function offsetGet($key)
    {
        return $this->offsetExists($key) ? $this->values[$key] : null;
    }
    public function offsetSet($key, $value)
    {
    }
    public function offsetUnset($key)
    {
    }
    public function getIterator()
    {
        $this->retreive(null);
        return new ArrayIterator($this->values);
    }

// --

    public function retreive($keys)
    {
        if ($this->retreived) return;

        $prop_name = 'values_'.$this->values_name;
        if (method_exists($this, $prop_name)) {
            $values = (array)call_user_func(array($this,$prop_name), $keys);
            foreach ($values as $k=>$v) $this->values[$k] = $v;
            if ($keys===null) $this->retreived = true;
        } elseif (property_exists($this, $prop_name)) {
            $this->values = static::$$prop_name;
            $this->retreived = true;
        } else {
            report_error("Enum参照先プロパティが定義されていません", array(
                "enum" => $this,
                "prop_name" => $prop_name,
            ));
        }
    }
}