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
        if (array_isset($_ENV, $key)) {
            return $this->looseCastValue(array_get($_ENV, $key));
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
    /**
     * @deprecated
     */
    public function getAll ()
    {
        report_error("@deprecated Env::getAll");
        return $_ENV;
    }
}
