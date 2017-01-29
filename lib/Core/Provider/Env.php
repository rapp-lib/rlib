<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;
use Dotenv\Dotenv;

class Env implements InvokableProvider
{
    public function invoke ($key, $default_value=null)
    {
        return $this->get($key,$default_value);
    }
    public function __construct ()
    {
        if (file_exists(constant("R_APP_ROOT_DIR").'/.env')) {
            $env = new Dotenv(constant("R_APP_ROOT_DIR"), '.env');
            $env->load();
        }
    }
    public function get ($key, $default_value=null)
    {
        return array_isset($_ENV, $key) ? array_get($_ENV, $key) : $default_value;
    }
    public function getAll ()
    {
        return $_ENV;
    }
}
