<?php
namespace R\Lib\Core;

class Config
{
    public function __invoke ($key)
    {
        return $this->config($key);
    }
    private $vars;
    public function __construct ()
    {
        $this->vars = array();
    }
    public function get ($key)
    {
        return array_get($this->vars, $key, 1);
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
