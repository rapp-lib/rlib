<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;

class Configure implements InvokableProvider
{
    public function invoke ($key)
    {
        return $this->config($key);
    }
    private $vars;
    public function __construct ()
    {
        $this->vars = & $GLOBALS["__REGISTRY__"];
    }
    public function get ($name)
    {
        return array_get($this->vars, $name);
    }
    public function getAll ()
    {
        return $this->vars;
    }
    public function set ($name, $value)
    {
        array_add($this->vars, $name, $value);
    }
    public function config ($value)
    {
        if (is_array($value)) {
            foreach ($value as $k=>$v) {
                $this->set($k, $v);
            }
        } else {
            return $this->get($value);
        }
    }
}
