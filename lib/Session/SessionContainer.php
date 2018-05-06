<?php
namespace R\Lib\Session;
use Zend\Session\Container as ZendSessionContainer;

class SessionContainer extends ZendSessionContainer
{
    public function & getRef ($key)
    {
        return \R\Lib\Util\Arr::array_get_ref($_SESSION[$this->getName()], $key);
    }
    public function get ($key)
    {
        return \R\Lib\Util\Arr::array_get($_SESSION[$this->getName()], $key);
    }
    public function exists ($key)
    {
        return \R\Lib\Util\Arr::array_isset($_SESSION[$this->getName()], $key);
    }
    public function set ($key, $value)
    {
        $ref = & $this->getRef($key);
        $ref = $value;
    }
    public function delete ($key)
    {
        return \R\Lib\Util\Arr::array_unset($_SESSION[$this->getName()], $key);
    }
    public function add ($key, $values=null)
    {
        return \R\Lib\Util\Arr::array_add($_SESSION[$this->getName()], $key, $values);
    }
}
