<?php
namespace R\Lib\Core;

class Configure
{
    private static $instance = null;
    /**
     * インスタンスを取得
     */
    public static function getInstance ($key=false)
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new Configure();
        }
        return $key===false
            ? self::$instance
            : self::$instance->config($key);
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