<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;
use Dotenv\Dotenv;

class Env implements InvokableProvider
{
    public function invoke ($key)
    {
        return $this->get($key);
    }
    public function load ($env_file)
    {
        if ( ! file_exists($env_file)) {
            return false;
        }
        $env = new Dotenv(dirname($env_file), basename($env_file));
        $env->load();
        return true;
    }
    public function get ($key)
    {
        return array_get($_ENV, $key);
    }
    public function getAll ()
    {
        return $_ENV;
    }
}
