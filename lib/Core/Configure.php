<?php
namespace R\Lib\Core;

class Configure
{
    private $vars;
    public function __construct ()
    {
        $this->vars = & $GLOBALS["__REGISTRY__"];
    }
    public function get ($name)
    {
        return array_get($this->vars, $name);
    }
    public function set ($name, $value)
    {
        array_add($this->vars, $name, $value);
    }
    public function config ($key)
    {
        if (is_array($key)) {
            foreach ($key as $k=>$v) {
                $this->set($k, $v);
            }
        } else {
            return $this->get($key);
        }
    }
}