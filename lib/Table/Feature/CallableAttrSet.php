<?php
namespace R\Lib\Table\Feature;

class CallableAttrSet implements \ArrayAccess
{
    protected $attrs;
    public function __construct($attrs)
    {
        $this->attrs = $attrs;
    }
    public function __invoke()
    {
        $args = func_get_args();
        return call_user_func_array($this->attrs["callback"], $args);
    }
    public function offsetExists($key)
    {
        return isset($this->attrs[$key]);
    }
    public function offsetGet($key)
    {
        return $this->attrs[$key];
    }
    public function offsetSet($key, $value)
    {
        return;
    }
    public function offsetUnset($key)
    {
        return;
    }
}
