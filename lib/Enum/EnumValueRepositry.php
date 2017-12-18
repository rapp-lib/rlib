<?php
namespace R\Lib\Enum;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class EnumValueRepositry implements ArrayAccess, IteratorAggregate
{
    private $repositry_name;
    private $values_name;
    private $retreived = false;
    private $values = array();
    public function __construct($repositry_name, $values_name)
    {
        $this->repositry_name = $repositry_name;
        $this->values_name = $values_name;
    }
    public function offsetExists($key)
    {
        if ( ! isset($this->values[self::encodeKey($key)])) $this->retreive(array($key));
        return isset($this->values[self::encodeKey($key)]);
    }
    public function offsetGet($key)
    {
        if ( ! $this->offsetExists($key)) return null;
        $value = $this->values[self::encodeKey($key)];
        $i18n_enum_key = $this->repositry_name.".".$this->values_name.".".self::encodeKey($key);
        $value = app()->i18n->getEnumValue($i18n_enum_key, $value);
        return $value;
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

    public function map($keys)
    {
        $this->retreive($keys);
        $values = array();
        foreach ($keys as $key) if (isset($this->values[self::encodeKey($key)])) {
            $values[self::encodeKey($key)] = $this->values[self::encodeKey($key)];
        }
        return $values;
    }
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

// --

    protected static function encodeKey($key)
    {
        return is_array($key) ? json_encode($key) : $key;
    }
}
