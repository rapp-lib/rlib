<?php
namespace R\Lib\Core;
use Dotenv\Dotenv;

class Env
{
    public function __invoke ($key)
    {
        return $this->get($key);
    }
    private $env = array();
    public function __construct ()
    {
        if (file_exists(constant("R_APP_ROOT_DIR")."/.env")) {
            $this->load(constant("R_APP_ROOT_DIR"));
        }
    }
    public function load ($app_root_dir)
    {
        $old = $_ENV;
        $env_loader = new Dotenv($app_root_dir);
        $env_loader->load();
        $this->env = $_ENV;
        $_ENV = $old;
    }
    public function get ($key, $default_value=null)
    {
        if (\R\Lib\Util\Arr::array_isset($this->env, $key)) {
            return $this->looseCastValue(\R\Lib\Util\Arr::array_get($this->env, $key));
        } else {
            return $default_value;
        }
    }
    private function looseCastValue ($value)
    {
        if ($value==="true") {
            return true;
        } elseif ($value==="false") {
            return false;
        } elseif ($value==="null") {
            return null;
        } elseif (ctype_digit($value)) {
            return (int)$value;
        } elseif (is_numeric($value)) {
            return (double)$value;
        } else {
            return $value;
        }
    }
}
