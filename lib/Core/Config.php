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
        $config_dir = constant("R_APP_ROOT_DIR")."/config";
        foreach (glob($config_dir."/*.config.php") as $config_file) {
            $this->config(include($config_file));
        }
        if ($app_env = $this->config("app.env")) {
            foreach (glob($config_dir."/env/".$app_env."/*.config.php") as $config_file) {
                $this->config(include($config_file));
            }
        }
    }
    public function get ($key, $default=null)
    {
        return \R\Lib\Util\Arr::array_get($this->vars, $key, 1);
    }
    public function getAll ()
    {
        return $this->vars;
    }
    public function set ($name, $value)
    {
        \R\Lib\Util\Arr::array_add($this->vars, $name, $value);
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
