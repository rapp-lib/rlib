<?php
namespace R\Lib\Session;
use Zend\Session\Container as ZendSessionContainer;

class SessionContainer extends ZendSessionContainer
{
    public function & getRef ($key)
    {
        $storage = & $_SESSION;
        if ( ! isset($storage[$this->getName()])) $storage[$this->getName()] = array();
        return array_get_ref($storage[$this->getName()], $key);
    }
    public function get ($key)
    {
        $storage = & $_SESSION;
        $ref = & $storage[$this->getName()];
        return array_get($ref, $key);
    }
    public function exists ($key)
    {
        $storage = & $_SESSION;
        $ref = & $storage[$this->getName()];
        return array_isset($ref, $key);
    }
    public function set ($key, $value)
    {
        $ref = & $this->getRef($key);
        $ref = $value;
    }
    public function delete ($key)
    {
        $storage = & $_SESSION;
        $ref = & $storage[$this->getName()];
        return array_unset($ref, $key);
    }
    public function add ($key, $values=null)
    {
        $storage = & $_SESSION;
        $ref = & $storage[$this->getName()];
        return array_add($ref, $key, $values);
    }
}
