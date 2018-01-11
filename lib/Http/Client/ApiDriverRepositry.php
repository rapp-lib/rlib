<?php
namespace R\Lib\Http\Client;

class ApiDriverRepositry
{
    private $config;
    private $resolver;
    public function __construct($resolver, $config=array())
    {
        $this->resolver = $resolver;
        $this->config = $config;
    }

    private $drivers = array();
    public function __get($name)
    {
        if ( ! $this->drivers[$name]) {
            $config = $this->config[$name];
            $class = call_user_func($this->resolver, $name);
            $this->drivers[$name] = new $class($config);
        }
        return $this->drivers[$name];
    }
}
